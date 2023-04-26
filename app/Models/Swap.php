<?php

namespace App\Models;

use App\Http\MusicService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        "description"
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
