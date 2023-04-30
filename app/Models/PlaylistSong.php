<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaylistSong extends Model
{
    use HasFactory;

    protected $fillable = [
        "playlist_id",
        "song_id"
    ];

    public static function createLink($playlistId, $songId): void
    {
        $link = new PlaylistSong([
            "playlist_id" => $playlistId,
            "song_id" => $songId
        ]);

        $link->save();
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }
}
