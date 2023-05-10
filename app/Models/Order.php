<?php

namespace App\Models;

use App\Types\PaymentType;
use App\Types\SubscriptionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "payment_type",
        "payment_amount",
        "subscription_type",
        "account_no"
    ];

    protected $casts = [
        "subscription_type" => SubscriptionType::class,
        "payment_type" => PaymentType::class
    ];

    protected $visible = [
        "payment_type",
        "payment_amount",
        "subscription_type"
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
