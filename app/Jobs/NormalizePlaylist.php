<?php
/*
 * Copyright (c) 2023. Gavin Kasdorf
 * This code is licensed under MIT license (see LICENSE.txt for details)
 */

namespace App\Jobs;

use App\AppleMusic\AppleMusic;
use App\Http\MusicService;
use App\Models\Playlist;
use App\Models\PlaylistSong;
use App\Models\Song;
use App\Models\Swap;
use App\Models\User;
use App\Spotify\Spotify;

class NormalizePlaylist
{
    //TODO Create a playlist first to get the id

    private MusicService $fromService;
    private MusicService $toService;
    private Swap $swap;
    private User $user;

    private mixed $fromApi;
    private mixed $toApi;

    private array $songsByServiceId;
    private int $playlistId;

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

        // Create arrays
        $this->songsByServiceId = [];

        // Decide which API instance we need to use
        if ($this->fromService == MusicService::SPOTIFY)
            $this->fromApi = new Spotify($user);
        else if ($this->fromService == MusicService::APPLE_MUSIC)
            $this->fromApi = new AppleMusic($user);

        if ($this->toService == MusicService::SPOTIFY)
            $this->toApi = new Spotify($this->user);
        else if ($this->toService == MusicService::APPLE_MUSIC)
            $this->toApi = new AppleMusic($this->user);
    }

    /**
     * Begin a normalization
     * @return ?array
     */
    public function normalize(): ?array
    {
        // Create a playlist
        $this->savePlaylist();

        // See which service we are coming from
        if ($this->fromService == MusicService::SPOTIFY)
            return $this->fromSpotify();
        else if ($this->fromService == MusicService::APPLE_MUSIC) {
            return $this->fromAppleMusic();
        }

        return null;
    }

    /**
     * Convert a playlist from Spotify to another service
     * @return array
     */
    private function fromSpotify(): array
    {
        // Get the playlist from Spotify
        $spotifyPlaylist = $this->fromApi->getPlaylist($this->swap->from_playlist_id);

        $this->swap->total_songs = count($spotifyPlaylist);
        $this->swap->save();

        // Loop through each of the songs
        foreach ($spotifyPlaylist as $spotifySong) {
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
        }

        // Get the name for the playlist
        $name = $this->fromApi->getPlaylistName($this->swap->from_playlist_id);

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
        if (str_contains($this->swap->from_playlist_id, "p.")) {
            $applePlaylist = $this->fromApi->getUserPlaylist($this->swap->from_playlist_id)->data;
        } else {
            $applePlaylist = $this->fromApi->getPlaylist($this->swap->from_playlist_id)->data;
        }

        $this->swap->total_songs = count($applePlaylist);
        $this->swap->save();

        // Loop through each song
        foreach ($applePlaylist as $appleSong) {
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
        }

        // Get the name for the playlist
        $name = $this->fromApi->getUserPlaylistName($this->swap->from_playlist_id);

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
            if (!$retry) {
                error_log("Didn't find the song, attempting to retry...");
                return $this->getAppleMusicSongId($song, true);
            } else {
                error_log("Still didn't find the track after a retry.");
                return null;
            }
        }
    }

    private function savePlaylist(): void
    {
        error_log($this->user->id);
        error_log($this->swap->from_service);

        //TODO update this when we add more services
        $playlist = new Playlist([
            "name" => $this->swap->playlist_name,
            "has_spotify" => $this->fromService == MusicService::SPOTIFY || $this->toService == MusicService::SPOTIFY,
            "has_apple_music" => $this->fromService == MusicService::APPLE_MUSIC || $this->toService == MusicService::APPLE_MUSIC,
            "user_id" => $this->user->id,
            "original_service" => $this->swap->from_service,
            "original_id" => $this->swap->from_playlist_id
        ]);

        error_log(json_encode($playlist));

        $playlist->save();

        $this->playlistId = $playlist->id;
    }
}
