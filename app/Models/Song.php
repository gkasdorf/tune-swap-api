<?php

namespace App\Models;

use App\Types\MusicService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * @property int $id
 * @property string $name
 * @property string $artist
 * @property string $album
 * @property string $genre
 * @property string $storefront
 * @property string $spotify_id
 * @property string $apple_music_id
 * @property string $tidal_id
 * @property string $pandora_id
 */
class Song extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "artist",
        "album",
        "spotify_id",
        "apple_music_id",
        "tidal_id",
        "pandora_id",
    ];

    public static function getById(MusicService $service, $id): ?Song
    {
        switch ($service) {
            case MusicService::SPOTIFY:
            {
                return Song::where("spotify_id", $id)->first();
            }

            case MusicService::APPLE_MUSIC:
            {
                return Song::where("apple_music_id", $id)->first();
            }

            case MusicService::TIDAL:
            {
                return Song::where("tidal_id", $id)->first();
            }

            case MusicService::PANDORA:
            {
                return Song::where("pandora_id", $id)->first();
            }
        }
    }

    public static function getByName($name, $album)
    {
        return Song::where([
            "name" => $name,
            "album" => $album
        ]);
    }
}
