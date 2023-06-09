<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $song_id
 * @property int $swap_id
 * @property Song $song
 * @property Swap $swap
 */
class SongNotFound extends Model
{
    use HasFactory;

    protected $table = "songs_not_found";

    protected $fillable = [
        "song_id",
        "swap_id",
    ];

    public function swap(): BelongsTo
    {
        return $this->belongsTo(Swap::class);
    }

    public function song(): HasOne
    {
        return $this->hasOne(Song::class, "id", "song_id");
    }
}
