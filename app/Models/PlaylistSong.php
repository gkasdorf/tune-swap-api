<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaylistSong extends Model
{
    use HasFactory;

    protected $fillable = [
        "playlist_id",
        "song_id"
    ];

    public static function createLink($playlistId, $songId)
    {
        $link = new PlaylistSong([
            "playlist_id" => $playlistId,
            "song_id" => $songId
        ]);

        $link->save();
    }
}
