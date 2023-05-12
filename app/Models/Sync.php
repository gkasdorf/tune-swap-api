<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $from_playlist_id
 * @property int $to_playlist_id
 * @property DateTime $last_synced
 * @property DateTime $last_updated
 * @property DateTime $last_checked
 * @property bool $syncing
 * @property User $user
 * @property Playlist $from_playlist
 * @property Playlist $to_playlist
 */
class Sync extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "from_playlist_id",
        "to_playlist_id",
        "last_synced",
        "last_checked",
        "last_updated",
        "syncing",
        "custom_time"
    ];

    protected $casts = [
        "last_synced" => "datetime",
        "last_checked" => "datetime",
        "last_updated" => "datetime",
        "syncing" => "bool"
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromPlaylist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class, "from_playlist_id");
    }

    public function toPlaylist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class, "to_playlist_id");
    }

    public function setChecked(): void
    {
        $this->last_checked = new DateTime();
        $this->save();
    }

    public function setUpdated(DateTime $time): void
    {
        $this->last_updated = $time;
        $this->save();
    }

    public function setSynced(): void
    {
        $this->last_synced = new DateTime();
        $this->save();
    }
}
