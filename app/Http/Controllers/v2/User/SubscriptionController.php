<?php

namespace App\Http\Controllers\v2\User;

use App\Api\AppStore\AppStore;
use App\Helpers\ApiResponse;
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
        $request->validate([
            "receipt" => "required"
        ]);

        $receipt = $request->input("receipt");

        $appStore = new AppStore();
        $verify = $appStore->verifyReceipt($receipt);

        if ($verify->status != 0) {
            return ApiResponse::fail("Invalid receipt.");
        }

        error_log(json_encode($verify));

        return ApiResponse::success();
    }
}
