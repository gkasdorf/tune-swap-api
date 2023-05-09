<?php

namespace App\Models;

use App\Http\MusicService;
use App\Types\SwapStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property SwapStatus $status
 * @property string $from_service
 * @property string $to_service
 * @property string $playlist_name
 * @property string $playlist_id
 * @property string $from_playlist_id
 * @property string $from_playlist_url
 * @property string $to_playlist_id
 * @property string $to_playlist_url
 * @property int $total_songs
 * @property int $songs_found
 * @property int $songs_not_found
 * @property string $description
 * @property User $user
 */
class Swap extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "from_service",
        "to_service",
        "from_playlist_id",
        "playlist_name",
        "status",
        "description",
        "from_playlist_id",
        "from_playlist_url",
        "to_playlist_id",
        "to_playlist_url"
    ];

    protected $casts = [
        "status" => SwapStatus::class,
        "fromService" => MusicService::class,
        "toService" => MusicService::class
    ];

    public function setStatus(SwapStatus $status)
    {
        $this->status = $status;
        $this->save();
    }

    public function setFromData($url)
    {
        $this->from_playlist_url = $url;
        $this->save();
    }

    public function setToData($data)
    {
        $this->to_playlist_id = $data->id;
        $this->to_playlist_url = $data->url;
        $this->save();
    }

    public static function getSwap(int $id)
    {
        return Swap::where("id", $id)->first();
    }

    public static function getSwaps(int $userId, $limit, $offset = 0)
    {
        return Swap::where("user_id", $userId)->orderBy('id', 'DESC')->skip($offset)->take($limit)->get();
    }

    public function getPlaylistName(): string
    {
        return $this->playlist_name;
    }

    public function getToService($string = false): MusicService|string
    {
        if ($string) {
            return $this->to_service;
        }

        return MusicService::from($this->to_service);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function songsNotFound(): HasMany
    {
        return $this->hasMany(SongNotFound::class);
    }
}
