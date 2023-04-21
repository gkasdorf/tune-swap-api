<?php

namespace App\Jobs;

use App\Api\AppleMusic\AppleMusic;
use App\Api\Spotify\Spotify;
use App\Api\Tidal\Tidal;
use App\Http\MusicService;
use App\Models\Playlist;
use App\Models\PlaylistSong;
use App\Models\Song;
use App\Models\SongNotFound;
use App\Models\Swap;
use App\Models\User;

class NormalizePlaylist
{
    private Swap $swap;
    private User $user;
    private Playlist $newPlaylist;

    private mixed $fromApi;
    private mixed $toApi;
    private bool $isLibrary = false;
    private array $songsByServiceId;
    private int $playlistId;
    private bool $retrySearch = false;

    public function __construct(Swap $swap, User $user)
    {
        // Set our swap and user
        $this->swap = $swap;
        $this->user = $user;

        // Check if this is a library swap
        if ($this->swap->from_playlist_id == "library") {
            $this->isLibrary = true;
        }

        // Set up our APIs
        switch (MusicService::from($this->swap->from_service)) {
            case MusicService::SPOTIFY:
            {
                $this->fromApi = new Spotify($this->user);
                break;
            }
            case MusicService::APPLE_MUSIC:
            {
                $this->fromApi = new AppleMusic($this->user);
                break;
            }
            case MusicService::TIDAL:
            {
                $this->fromApi = new Tidal($this->user);
                break;
            }
            case MusicService::PANDORA:
                throw new \Exception('To be implemented');
        }

        switch (MusicService::from($this->swap->to_service)) {
            case MusicService::SPOTIFY:
            {
                $this->toApi = new Spotify($this->user);
                break;
            }
            case MusicService::APPLE_MUSIC:
            {
                $this->toApi = new AppleMusic($this->user);
                break;
            }
            case MusicService::TIDAL:
            {
                $this->toApi = new Tidal($this->user);
                break;
            }
            case MusicService::PANDORA:
                throw new \Exception('To be implemented');
        }
    }

    public function normalize(): ?array
    {
        // Create a new playlist
        $this->createPlaylist();

        // Get the playlist from api
        $playlist = $this->isLibrary ? $this->fromApi->getLibrary() : $this->fromApi->getPlaylist($this->swap->from_playlist_id);

        // Set the count of songs
        $this->swap->total_songs = count($playlist);
        $this->swap->save();

        // Loop through the songs
        foreach ($playlist as $song) {
            try {
                // See if our song exists already
                $savedSong = Song::getById(MusicService::from($this->swap->from_service), $song->id);

                // If not we need to make one
                if (!$savedSong) {
                    $savedSong = new Song([
                        "name" => $song->name,
                        "artist" => $song->artist,
                        "album" => $song->album,
                    ]);

                    // Determine which ID we are going to set
                    switch (MusicService::from($this->swap->from_service)) {
                        case MusicService::SPOTIFY:
                        {
                            $savedSong->spotify_id = $song->id;
                            break;
                        }
                        case MusicService::APPLE_MUSIC:
                        {
                            $savedSong->apple_music_id = $song->id;
                            break;
                        }
                        case MusicService::TIDAL:
                        {
                            $savedSong->tidal_id = $song->id;
                            break;
                        }
                    }
                }


                // Let's add the new service ID to the song
                $savedSong = $this->songTo($savedSong);

                // Save it!
                $savedSong->save();

                PlaylistSong::createLink($this->newPlaylist->id, $savedSong->id);
                usleep(500);
            } catch (\Exception $e) {
                error_log("There was an error!");
                error_log($e->getMessage());
                error_log($e->getLine());
                error_log($e->getFile());
//                error_log($e->getTraceAsString());
//                error_log("\\n\\n\\n\\n");
            }
        }

        $name = $this->isLibrary ? "Library" : $this->fromApi->getPlaylistName($this->swap->from_playlist_id);

        return [
            "name" => $name,
            "ids" => $this->songsByServiceId
        ];
    }

    /**
     * Create playlist in the database
     * @return void
     */
    private function createPlaylist(): void
    {
        // Create a playlist in the database
        $playlist = new Playlist([
            "name" => $this->swap->playlist_name,
            "has_spotify" => $this->swap->from_service == MusicService::SPOTIFY || $this->swap->to_service == MusicService::SPOTIFY,
            "has_apple_music" => $this->swap->from_service == MusicService::APPLE_MUSIC || $this->swap->to_service == MusicService::APPLE_MUSIC,
            "has_tidal" => $this->swap->from_service == MusicService::TIDAL || $this->swap->to_service == MusicService::TIDAL,
            "user_id" => $this->user->id,
            "original_service" => $this->swap->from_service,
            "original_id" => $this->swap->from_playlist_id
        ]);

        $playlist->save();

        $this->newPlaylist = $playlist;
    }

    /**
     * Check if the song already has the wanted service ID
     * @param $song
     * @return string|null
     */
    private function checkIfExists($song): ?string
    {
        switch (MusicService::from($this->swap->to_service)) {
            case MusicService::SPOTIFY:
            {
                return $song ? $song->spotify_id : null;
            }
            case MusicService::APPLE_MUSIC:
            {
                return $song ? $song->apple_music_id : null;
            }
            case MusicService::TIDAL:
            {
                return $song ? $song->tidal_id : null;
            }
            default:
            {
                return null;
            }
        }
    }

    /**
     * Get the song ID for the wanted service
     * @param $song
     * @return Song
     */
    private function songTo($song): Song
    {
        // See if we already have it
        $checkRes = $this->checkIfExists($song);

        // If we have it, just add the id and return
        if ($checkRes) {
            error_log("It exists!!");
            $this->songsByServiceId[] = $checkRes;
            $this->swap->songs_found++;
            $this->swap->save();
            return $song;
        }

        // Make our search term
        $term = $this->prepareSearch($song);
        error_log("Term is: {$term}");

        // Perform the search
        $search = $this->toApi->search($term);

        // If we didn't find it this time, we will try again with a different term
        if (!$search) {
            error_log("Didn't find it. Retrying...");

            $term = $this->prepareRetry($song);
            error_log("New term: {$term}");

            $search = $this->toApi->search($term);
        }

        // If we still didn't find it, we will just move on
        if (!$search) {
            $this->swap->songs_not_found++;
            $this->swap->save();

            // Add it to the database
            $notFound = new SongNotFound([
                "song_id" => $song->id,
                "swap_id" => $this->swap->id
            ]);

            $notFound->save();

            return $song;
        }

        // Add the song's id
        $this->songsByServiceId[] = $search->id;
        // Increment
        $this->swap->songs_found++;
        $this->swap->save();

        // Add the new ID to the song
        $song = $this->addNewId($song, $search->id);

        // Return the song
        return $song;
    }

    /**
     * Prepare a term for search
     * @param $song
     * @return string
     */
    private function prepareSearch($song): string
    {
        switch (MusicService::from($this->swap->to_service)) {
            case MusicService::SPOTIFY:
            {
                return "{$song->name} {$song->artist} {$song->album}";
            }
            case MusicService::APPLE_MUSIC:
            {
                $term = "{$song->name} {$song->artist} {$song->album}";
                $term = str_replace(" ", "+", $term);
                $term = str_replace("'", "", $term);
                $term = str_replace("-", "", $term);
                $term = str_replace("++", "+", $term);

                return $term;
            }
            case MusicService::TIDAL:
            {
                $name = explode("(", $song->name)[0];
                $term = "{$name} {$song->artist}";
                $term = str_replace("'", "", $term);

                return $term;
            }
            default:
            {
                return "";
            }
        }
    }

    /**
     * Prepare a less strict term for search, may result in mismatch
     * @param $song
     * @return string
     */
    private function prepareRetry($song): string
    {
        $name = strtolower($song->name);
        $name = explode("recorded", $name)[0];
        $name = explode("remaster", $name)[0];
        $name = explode("(", $name)[0];
        $name = explode("feat", $name)[0];
        $name = explode("ft", $name)[0];

        switch (MusicService::from($this->swap->to_service)) {
            case MusicService::SPOTIFY:
            {
                return "{$name} {$song->artist}";
            }
            case MusicService::APPLE_MUSIC:
            {
                $term = "{$name} {$song->artist}";
                $term = str_replace(" ", "+", $term);
                $term = str_replace("'", "", $term);
                $term = str_replace("-", "", $term);
                $term = str_replace("++", "+", $term);

                return $term;
            }
            case MusicService::TIDAL:
            {
                $name = explode("(", $song->name)[0];
                $term = "{$name} {$song->artist}";
                $term = str_replace("'", "", $term);

                return $term;
            }
            default:
            {
                return "";
            }
        }
    }

    /**
     * Add the id to the correct service in the song
     * @param $song
     * @param $newId
     * @return Song
     */
    private function addNewId($song, $newId): Song
    {
        switch (MusicService::from($this->swap->to_service)) {
            case MusicService::SPOTIFY:
            {
                $song->spotify_id = $newId;
                break;
            }
            case MusicService::APPLE_MUSIC:
            {
                $song->apple_music_id = $newId;
                break;
            }
            case MusicService::TIDAL:
            {
                $song->tidal_id = $newId;
                break;
            }
        }

        return $song;
    }
}
