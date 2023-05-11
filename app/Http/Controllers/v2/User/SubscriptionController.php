<?php

namespace App\Http\Controllers\v2\User;

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
}
