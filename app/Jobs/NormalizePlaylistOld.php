<?php
/*
 * Copyright (c) 2023. Gavin Kasdorf
 * This code is licensed under MIT license (see LICENSE.txt for details)
 */

namespace App\Jobs;

use App\Api\AppleMusic\AppleMusicv1;
use App\Api\Spotify\Spotifyv1;
use App\Api\Tidal\Tidalv1;
use App\Http\MusicService;
use App\Models\Playlist;
use App\Models\PlaylistSong;
use App\Models\Song;
use App\Models\Swap;
use App\Models\User;

class NormalizePlaylistOld
{
    //TODO Create a playlist first to get the id

    private MusicService $fromService;
    private MusicService $toService;
    private Swap $swap;
    private User $user;

    private mixed $fromApi;
    private mixed $toApi;

    private bool $isLibrary = false;

    private array $songsByServiceId;
    private int $playlistId;

    private $retrySearch = false;

    /**
     * Create a new Normalize instance
     * @param MusicService $fromService
     * @param MusicService $toService
     * @param Swap $swap
     * @param User $user
     */
    //public function __construct(MusicService $fromService, MusicService $toService, string $playlistId, User $user)
    public function __construct(MusicService $fromService, MusicService $toService, Swap $swap, User $user)

    {
        // Set our values
        $this->fromService = $fromService;
        $this->toService = $toService;
        $this->swap = $swap;
        $this->user = $user;

        if ($this->swap->from_playlist_id === "library") {
            $this->isLibrary = true;
        }

        // Create arrays
        $this->songsByServiceId = [];

        // Decide which API instance we need to use
        if ($this->fromService == MusicService::SPOTIFY)
            $this->fromApi = new Spotifyv1($this->user);
        else if ($this->fromService == MusicService::APPLE_MUSIC)
            $this->fromApi = new AppleMusicv1($this->user);
        else if ($this->fromService == MusicService::TIDAL)
            $this->fromApi = new Tidalv1($this->user);

        if ($this->toService == MusicService::SPOTIFY)
            $this->toApi = new Spotifyv1($this->user);
        else if ($this->toService == MusicService::APPLE_MUSIC)
            $this->toApi = new AppleMusicv1($this->user);
        else if ($this->toService == MusicService::TIDAL)
            $this->toApi = new Tidalv1($this->user);
    }

    /**
     * Begin a normalization
     * @return ?array
     */
    public function normalize(): ?array
    {
        // Create a playlist
        $this->savePlaylist();

        switch ($this->fromService) {
            case MusicService::SPOTIFY:
            {
                return $this->fromSpotify();
            }
            case MusicService::APPLE_MUSIC:
            {
                return $this->fromAppleMusic();
            }
            case MusicService::TIDAL:
            {
                return $this->fromTidal();
            }
            default:
            {
                return null;
            }
        }
    }

    /**
     * Convert a playlist from Spotify to another service
     * @return array
     */
    private function fromSpotify(): array
    {
        // Get the playlist from Spotify
        $spotifyPlaylist = $this->isLibrary ? $this->fromApi->getLibrary() : $this->fromApi->getPlaylist($this->swap->from_playlist_id);

        $this->swap->total_songs = count($spotifyPlaylist);
        $this->swap->save();

        // Loop through each of the songs
        foreach ($spotifyPlaylist as $spotifySong) {
            try {
                // Try to get the song if we have it already
                $song = Song::getById(MusicService::SPOTIFY, $spotifySong->track->id);

                // If we don't have it, lets make a new one
                if (!$song) {
                    $song = new Song([
                        "name" => $spotifySong->track->name,
                        "artist" => $spotifySong->track->artists[0]->name,
                        "album" => $spotifySong->track->album->name,
                        "spotify_id" => $spotifySong->track->id
                    ]);
                }

                // Add the new service ID to the song
                $song = $this->songTo($song);

                // Save the song
                $song->save();

                // Create playlist link
                PlaylistSong::createLink($this->playlistId, $song->id);
            } catch (\Exception $e) {
                error_log($e);
            }
        }

        // Get the name for the playlist
        $name = $this->isLibrary ? "Library" : $this->fromApi->getPlaylistName($this->swap->from_playlist_id);

        // Return the results
        return [
            "name" => $name,
            "ids" => $this->songsByServiceId
        ];
    }

    /**
     * Convert a playlist from Apple Music to another service
     * @return array
     */
    private function fromAppleMusic(): array
    {
        // Determine if this is a user playlist or a catalog playlist. Get the playlist
        if ($this->isLibrary) {
            $applePlaylist = $this->fromApi->getLibrary()->data;
        } else if (str_contains($this->swap->from_playlist_id, "p.")) {
            $applePlaylist = $this->fromApi->getUserPlaylist($this->swap->from_playlist_id)->data;
        } else {
            $applePlaylist = $this->fromApi->getPlaylist($this->swap->from_playlist_id)->data;
        }

        $this->swap->total_songs = count($applePlaylist);
        $this->swap->save();

        // Loop through each song
        foreach ($applePlaylist as $appleSong) {
            try {
                // Get the catalog id
                if (isset($appleSong->attributes->playParams)) {
                    $catalogId = $appleSong->attributes->playParams->catalogId;
                } else {
                    $catalogId = null;
                }

                // See if we have the song
                $song = Song::getById(MusicService::APPLE_MUSIC, $catalogId);

                // If not we will make a new one
                if (!$song) {
                    // Because Apple contains the whole list of artists whereas spotify gives us just one, we will convert to just one
                    $artistName = explode("&", $appleSong->attributes->artistName)[0];
                    $artistName = explode(",", $artistName)[0];

                    // Make the song
                    $song = new Song([
                        "name" => $appleSong->attributes->name,
                        "artist" => $artistName,
                        "album" => $appleSong->attributes->albumName,
                        "apple_music_id" => $catalogId
                    ]);
                }

                // Convert the song
                $song = $this->songTo($song);

                // Save the song
                $song->save();

                // Create playlist link
                PlaylistSong::createLink($this->playlistId, $song->id);
            } catch (\Exception $e) {
                error_log($e);
            }
        }

        //TODO If it ISNT a user playlist...
        // Get the name for the playlist
        $name = $this->isLibrary ? "Library" : $this->fromApi->getUserPlaylistName($this->swap->from_playlist_id);

        // Return the results
        return [
            "name" => $name,
            "ids" => $this->songsByServiceId
        ];
    }

    private function fromTidal(): array
    {
        $tidalPlaylist = $this->isLibrary ? $this->fromApi->getLibrary() : $this->fromApi->getPlaylist($this->swap->from_playlist_id);

        $this->swap->total_songs = count($tidalPlaylist);
        $this->swap->save();

        // Loop through each song
        foreach ($tidalPlaylist as $tidalSong) {
            if ($this->isLibrary) {
                $tidalSong = $tidalSong->item;
            }

            try {
                // See if we have the song
                $song = Song::getById(MusicService::TIDAL, $tidalSong->id);

                // If not we will make a new one
                if (!$song) {
                    // Make the song
                    $song = new Song([
                        "name" => $tidalSong->title,
                        "artist" => $tidalSong->artist->name,
                        "album" => $tidalSong->album->title,
                        "tidal_id" => $tidalSong->id
                    ]);
                }

                // Convert the song
                $song = $this->songTo($song);

                // Save the song
                $song->save();

                // Create playlist link
                PlaylistSong::createLink($this->playlistId, $song->id);
            } catch (\Exception $e) {
                error_log($e);
            }
        }

        // Get the name for the playlist
        $name = $this->isLibrary ? "Library" : $this->fromApi->getPlaylistName($this->swap->from_playlist_id);

        // Return the results
        return [
            "name" => $name,
            "ids" => $this->songsByServiceId
        ];
    }

    /**
     * Decide which service we are going to be converting the song to
     * @param Song $song
     * @return Song
     */
    private function songTo(Song $song): Song
    {
        // Figure out which service we are converting to
        if ($this->toService == MusicService::SPOTIFY) {
            if ($song->spotify_id) {
                // Add the id to the array
                $this->songsByServiceId[] = $song->spotify_id;

                // Update the count
                $this->swap->songs_found++;
                $this->swap->save();

                // Return the song
                return $song;
            }

            // Try to get the id
            $id = $this->getSpotifySongId($song);

            if ($id) {
                // Add the id to the array to return
                $this->songsByServiceId[] = $id;

                // Update the count
                $this->swap->songs_found++;
                $this->swap->save();
            } else {
                // Update the count
                $this->swap->songs_not_found++;
                $this->swap->save();
            }

            // Set the id to whatever it was
            $song->spotify_id = $id;
            // Take a deep breath! You're workin hard!!!
            usleep(500);
        } else if ($this->toService == MusicService::APPLE_MUSIC) {
            // Make sure that retry is false
            $this->retrySearch = false;

            // See if we already have the id for the song
            if ($song->apple_music_id) {
                // Add the id to the array to return
                $this->songsByServiceId[] = $song->apple_music_id;

                // Update the count
                $this->swap->songs_found++;
                $this->swap->save();

                // Return the song
                return $song;
            }

            // Try to get the ID for the song from Apple
            $id = $this->getAppleMusicSongId($song);

            // If we got a result, add it to the array for return
            if ($id) {
                $this->songsByServiceId[] = $id;

                // Update the count
                $this->swap->songs_found++;
                $this->swap->save();
            } else {
                error_log("We didn't find the song.");
                // Update the count
                $this->swap->songs_not_found++;
                $this->swap->save();
            }

            // Set the id to whatever it was, including null
            $song->apple_music_id = $id;
            // Breathe for a minute, you got this!!
            usleep(500);
        } else if ($this->toService == MusicService::TIDAL) {
            if ($song->tidal_id) {
                $this->songsByServiceId[] = $song->tidal_id;

                $this->swap->songs_found++;
                $this->swap->save();

                return $song;
            }

            $id = $this->getTidalSongId($song);

            if ($id) {
                $this->songsByServiceId[] = $id;

                $this->swap->songs_found++;
                $this->swap->save();
            } else {
                $this->swap->songs_not_found++;
                $this->swap->save();
            }

            $song->tidal_id = $id;

            usleep(500);
        }

        // Return the song
        return $song;
    }

    /**
     * Get the Spotify id for a song or return null if it doesn't exist on Spotify
     * @param Song $song
     * @return ?string
     */
    private function getSpotifySongId(Song $song): ?string
    {
        // Create our search data
        $search = $this->toApi->search([
            "name" => $song->name,
            "artist" => $song->artist,
            "album" => $song->album
        ]);

        // Try to get the id from the result, else return null
        try {
            return $search->id;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Get the Apple Music id for a song or return null if it doesn't exist on Apple Music
     * @param Song $song
     * @return ?string
     */
    private function getAppleMusicSongId(Song $song, bool $retry = false): ?string
    {
        // Create our search data
        $search = $this->toApi->search([
            "name" => $song->name,
            "artist" => $song->artist,
            "album" => $song->album
        ], $retry);

        // Try to get the id from the result, else return null
        try {
            $id = $search->results->songs->data[0]->id;

            return $id;
        } catch (\Exception) {
            // If we have not already retried, lets do that
            if (!$this->retrySearch) {
                error_log("Didn't find the song, attempting to retry...");
                $this->retrySearch = true;
                return $this->getAppleMusicSongId($song, true);
            }

            error_log("Still didn't find the track after a retry. Moving on.");
            return null;
        }
    }

    private function getTidalSongId(Song $song): ?string
    {
        $search = $this->toApi->search([
            "name" => $song->name,
            "artist" => $song->artist,
            "album" => $song->album
        ]);

        try {
            return $search->id;
        } catch (\Exception) {
            return null;
        }
    }

    private function savePlaylist(): void
    {
        //TODO update this when we add more services
        $playlist = new Playlist([
            "name" => $this->swap->playlist_name,
            "has_spotify" => $this->fromService == MusicService::SPOTIFY || $this->toService == MusicService::SPOTIFY,
            "has_apple_music" => $this->fromService == MusicService::APPLE_MUSIC || $this->toService == MusicService::APPLE_MUSIC,
            "user_id" => $this->user->id,
            "original_service" => $this->swap->from_service,
            "original_id" => $this->swap->from_playlist_id
        ]);

        $playlist->save();

        $this->playlistId = $playlist->id;
    }
}