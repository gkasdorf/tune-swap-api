<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $access_id
 * @property int $playlist_id
 * @property int $saves
 * @property bool $ready
 * @property Playlist $playlist
 * @property User $user
 */
class Share extends Model
{
    use HasFactory;

    protected $fillable = [
        "id",
        "access_id",
        "playlist_id",
        "saves"
    ];

    protected $casts = [
        "ready" => "boolean"
    ];

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
