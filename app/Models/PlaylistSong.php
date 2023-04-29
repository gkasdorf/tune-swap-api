<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlaylistSong extends Model
{
    use HasFactory;

    public Song $song;

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

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function song(): HasOne
    {
        return $this->hasOne(Song::class);
    }
}
