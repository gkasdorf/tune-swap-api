<?php

namespace App\Models;

use App\Http\MusicService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Copy extends Model
{
    use HasFactory;

    public Share $share;
    public MusicService $service;
    public int $progress;
    public SwapStatus $status;

    protected $fillable = [
        "user_id",
        "share_id"
    ];

    protected $casts = [
        "service" => MusicService::class,
        "status" => SwapStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function share(): HasOne
    {
        return $this->hasOne(Share::class);
    }
}
