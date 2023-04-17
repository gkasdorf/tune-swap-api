<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Playlist extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "songs",
        "has_spotify",
        "has_apple_music",
        "has_tidal",
        "has_pandora",
        "user_id",
        "original_service",
        "original_id"
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function playlistSongs(): HasMany
    {
        return $this->hasMany(PlaylistSong::class);
    }
}
