<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $genre
 * @property int $swaps
 * @property string $service
 * @property string $service_id
 * @property User $user
 * @property
 */
class Playlist extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "songs",
        "user_id",
        "service",
        "service_id"
    ];

    public function user(): BelongsTo|User
    {
        return $this->belongsTo(User::class);
    }

    public function playlistSongs(): HasMany
    {
        return $this->hasMany(PlaylistSong::class);
    }
}
