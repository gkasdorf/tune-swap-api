<?php

namespace App\Jobs\Swap;

use App\Models\Playlist;
use App\Models\PlaylistSong;
use App\Models\Song;
use App\Models\Swap;
use App\Models\User;
use App\Types\MusicService;
use App\Types\ParsedSong;

class SwapHelper
{
    private mixed $api;
    private ?User $user;
    private ?Swap $swap;

    public function __constructor(mixed $api, mixed $user = null, mixed $swap = null)
    {

    }

    public static function createSong(ParsedSong $parsedSong, MusicService|string $service): Song
    {
        $song = new Song([
            "name" => $parsedSong->name,
            "artist" => $parsedSong->artist,
            "album" => $parsedSong->album
        ]);

        switch (MusicService::from($service)) {
            case MusicService::SPOTIFY:
            {
                $song->spotify_id = $parsedSong->id;
                break;
            }
            case MusicService::APPLE_MUSIC:
            {
                $song->apple_music_id = $parsedSong->id;
                break;
            }
            case MusicService::TIDAL:
            {
                $song->tidal_id = $parsedSong->id;
                break;
            }
        }

        $song->save();

        return $song;
    }

    public static function createPlaylist(Playlist $playlist, mixed $api): array
    {
        $playlistItems = ($playlist->service_id != "library") ? $api->getPlaylist($playlist->service_id) : $api->getLibrary();

        $songs = [];
        $playlistSongs = [];

        foreach ($playlistItems as $item) {
            try {
                $song = Song::getById(MusicService::from($playlist->service), $item->id);

                if (!$song) {
                    $song = new Song([
                        "name" => $item->name,
                        "artist" => $item->artist,
                        "album" => $item->album
                    ]);

                    switch (MusicService::from($playlist->service)) {
                        case MusicService::SPOTIFY:
                        {
                            $song->spotify_id = $item->id;
                            break;
                        }
                        case MusicService::APPLE_MUSIC:
                        {
                            $song->apple_music_id = $item->id;
                            break;
                        }
                        case MusicService::TIDAL:
                        {
                            $song->tidal_id = $item->id;
                            break;
                        }
                    }

                    $song->save();
                }

                $songs[] = $song;

                $playlistSongs[] = [
                    "playlist_id" => $playlist->id,
                    "song_id" => $song->id
                ];
            } catch (\Exception $e) {
                error_log(json_encode($e));
            }
        }

        PlaylistSong::insert($playlistSongs);

        return $songs;
    }

    public static function findTrackId(Song $song, MusicService $service, mixed $api): ?array
    {
        $checkRes = self::checkIfExists($song, $service);

        if ($checkRes) {
            return [
                "trackId" => $checkRes,
                "usedApi" => false
            ];
        }

        $term = self::prepareSearch($song, $service);

        $search = $api->search($term);

        if (!$search) {
            $term = self::prepareRetry($song, $service);

            $search = $api->search($term);
        }

        if (!$search) return null;

        $song = self::addNewId($song, $search->id, $service);
        $song->save();

        return [
            "trackId" => $search->id,
            "usedApi" => true
        ];
    }

    private static function checkIfExists(Song $song, MusicService $service): ?string
    {
        switch ($service) {
            case MusicService::SPOTIFY:
            {
                return $song->spotify_id;
            }
            case MusicService::APPLE_MUSIC:
            {
                return $song->apple_music_id;
            }
            case MusicService::TIDAL:
            {
                return $song->tidal_id;
            }
            default:
            {
                return null;
            }
        }
    }

    private static function prepareSearch($song, $service): string
    {
        switch ($service) {
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

    private static function prepareRetry($song, $service): string
    {
        $name = strtolower($song->name);
        $name = explode("recorded", $name)[0];
        $name = explode("remaster", $name)[0];
        $name = explode("(", $name)[0];
        $name = explode("feat", $name)[0];
        $name = explode("ft", $name)[0];

        switch ($service) {
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

    private static function addNewId($song, $newId, $service): Song
    {
        switch ($service) {
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
