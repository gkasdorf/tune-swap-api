<?php

namespace App\Http\Controllers\v2\User;

use App\Api\AppStore\AppStore;
use App\Api\GooglePlay\GooglePlay;
use App\Helpers\ApiResponse;
use App\Models\Order;
use App\Models\Subscription;
use App\Types\PaymentType;
use App\Types\SubscriptionType;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends \App\Http\Controllers\Controller
{
    public function getSubscription(Request $request): JsonResponse
    {
        $subscription = $request->user()->getSubscription();

        return ApiResponse::success([
            "message" => "Successfully retrieved subscription.",
            "subscription" => $subscription
        ]);
    }

    public function verifySubscriptionIos(Request $request): JsonResponse
    {
        try {
            $request->validate([
                "receipt" => "required"
            ]);

            $receipt = $request->input("receipt");

            $appStore = new AppStore();
            $verify = $appStore->verifyReceipt($receipt);

            if ($verify->status != 0) {
                return ApiResponse::fail("Invalid receipt.");
            }

            $latest = $verify->latest_receipt_info[0];

            $dataToStore = [
                "product_id" => $latest->product_id,
                "transaction_id" => $latest->transaction_id,
                "original_transaction_id" => $latest->original_transaction_id,
                "purchase_date" => $latest->purchase_date,
                "purchase_date_ms" => $latest->purchase_date_ms,
                "expires_date" => $latest->expires_date,
                "expires_date_ms" => $latest->expires_date_ms,
                "is_trial_period" => $latest->is_trial_period
            ];

            $order = new Order([
                "user_id" => $request->user()->id,
                "payment_type" => PaymentType::APPLE,
                "payment_amount" => 0.00,
                "subscription_type" => $latest->product_id == "com.gkasdorf.tuneswap.turbo" ? SubscriptionType::TURBO : SubscriptionType::PLUS,
                "order_data" => json_encode($dataToStore),
                "transaction_id" => $latest->transaction_id
            ]);

            $order->save();

            $subscription = new Subscription([
                "user_id" => $request->user()->id,
                "start_date" => new DateTime(),
                "end_date" => date($latest->expires_date_ms / 1000),
                "subscription_type" => $latest->product_id == "com.gkasdorf.tuneswap.turbo" ? SubscriptionType::TURBO : SubscriptionType::PLUS,
            ]);

            $subscription->save();

            return ApiResponse::success([
                "subscription" => $subscription
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getLine());
            error_log($e->getFile());

            return ApiResponse::error($e->getMessage());
        }
    }

    public function verifySubscriptionAndroid(Request $request): JsonResponse
    {
        try {
            $request->validate([
                "packageName" => "required",
                "receipt" => "required",
                "productId" => "required"
            ]);

            $googlePlay = new GooglePlay();
            error_log("We are here.");
            $verify = $googlePlay->verifyReceipt(
                $request->input("packageName"),
                $request->input("productId"),
                $request->input("receipt")
            );

            if (!$verify) {
                return ApiResponse::fail("Invalid receipt.");
            }

            $order = new Order([
                "user_id" => $request->user()->id,
                "payment_type" => PaymentType::GOOGLE_PLAY,
                "payment_amount" => 0.0,
                "subscription_type" => $request->input("productId") == "com.gkasdorf.tuneswap.turbo" ? SubscriptionType::TURBO : SubscriptionType::PLUS,
                "order_data" => json_encode($verify),
                "transaction_id" => $verify->orderId
            ]);

            $order->save();

            $subscription = new Subscription([
                "user_id" => $request->user()->id,
                "start_date" => Carbon::createFromTimestamp($verify->startTimeMillis / 1000)->toDateTimeString(),
                "end_date" => Carbon::createFromTimestamp($verify->expiryTimeMillis / 1000)->toDateTimeString(),
                "subscription_type" => $request->input("productId") == "com.gkasdorf.tuneswap.turbo" ? SubscriptionType::TURBO : SubscriptionType::PLUS,
            ]);

            $subscription->save();

            return ApiResponse::success([
                "subscription" => $subscription
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getLine());
            error_log($e->getFile());

            return ApiResponse::error($e->getMessage());
        }
    }
}
