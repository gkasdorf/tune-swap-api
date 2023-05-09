<?php

namespace App\Models;

use App\Http\MusicService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $share_id
 * @property MusicService $service
 * @property SwapStatus $status
 * @property User $user
 * @property Share $share
 * @property string $service_url
 */
class Copy extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "share_id",
        "service"
    ];

    protected $casts = [
        "service" => MusicService::class,
        "status" => SwapStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function share(): BelongsTo
    {
        return $this->belongsTo(Share::class);
    }
}
