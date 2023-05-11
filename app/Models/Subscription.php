<?php

namespace App\Models;

use App\Types\SubscriptionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * @property int $user_id
 * @property string $start_date
 * @property string $end_date
 * @property SubscriptionType $subscription_type
 * @property User $user
 */
class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "start_date",
        "end_date",
        "subscription_type"
    ];

    protected $casts = [
        "subscription_type" => SubscriptionType::class
    ];

    protected $visible = [
        "start_date",
        "end_date",
        "subscription_type"
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
