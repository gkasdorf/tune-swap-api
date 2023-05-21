<?php

namespace App\Models;

use App\Types\PaymentType;
use App\Types\SubscriptionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property PaymentType $payment_type
 * @property float $payment_amount
 * @property SubscriptionType $subscription_type
 * @property string $account_no
 * @property string $transaction_id
 * @property string $order_data
 */
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "payment_type",
        "payment_amount",
        "subscription_type",
        "account_no",
        "order_data",
        "transaction_id"
    ];

    protected $casts = [
        "subscription_type" => SubscriptionType::class,
        "payment_type" => PaymentType::class
    ];

    protected $visible = [
        "payment_type",
        "payment_amount",
        "subscription_type",
        "transaction_id",
        "order_data"
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getUserByOriginalOrderIdApple(string $originalOrderId): ?User
    {
        $order = Order::where("transaction_id", $originalOrderId)
            ->where("payment_type", PaymentType::APPLE)
            ->first();

        if (!$order) {
            return null;
        }

        return $order->user;
    }
}
