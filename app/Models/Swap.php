<?php

namespace App\Models;

use App\Http\MusicService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Swap extends Model
{
    use HasFactory;

    public function setStatus(SwapStatus $status)
    {
        $this->status = $status;
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

    protected $casts = [
        "status" => SwapStatus::class,
        "fromService" => MusicService::class,
        "toService" => MusicService::class
    ];
}

enum SwapStatus: string
{
    case CREATED = "Created";
    case QUEUED = "Queued";
    case FINDING_MUSIC = "Finding Music";
    case BUILDING_PLAYLIST = "Building Playlist";
    case CLEANING_UP = "Cleaning Up";
    case COMPLETED = "Completed";
    case CANCELLED = "Cancelled";
    case ERROR = "Error";
}
