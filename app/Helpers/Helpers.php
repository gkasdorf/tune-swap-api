<?php

namespace App\Helpers;

use App\Api\AppleMusic\AppleMusic;
use App\Api\Spotify\Spotify;
use App\Api\Tidal\Tidal;
use App\Models\User;
use App\Types\MusicService;
use Exception;

class Helpers
{
    public static function generateUniqueId($length = 15): string
    {
        $bytes = null;

        if (function_exists("random_bytes")) {
            $bytes = random_bytes(ceil($length / 2));
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($length / 2));
        }

        return substr(bin2hex($bytes), 0, $length);
    }

    /**
     * @throws Exception
     */
    public static function serviceToApi(MusicService $service, User $user): Spotify|AppleMusic|Tidal
    {
        switch ($service) {
            case MusicService::SPOTIFY:
            {
                return new Spotify($user);
            }
            case MusicService::APPLE_MUSIC:
            {
                return new AppleMusic($user);
            }
            case MusicService::TIDAL:
            {
                return new Tidal($user);
            }
            default:
            {
                throw new Exception("Invalid service");
            }
        }
    }

    public static function serviceToColumnName(MusicService|string $service): string
    {
        if (is_string($service)) {
            $service = MusicService::from($service);
        }

        switch ($service) {
            case MusicService::SPOTIFY:
            {
                return "spotify_id";
            }
            case MusicService::APPLE_MUSIC:
            {
                return "apple_music_id";
            }
            case MusicService::TIDAL:
            {
                return "tidal_id";
            }
            default:
            {
                throw new Exception("Invalid service");
            }
        }
    }
}
