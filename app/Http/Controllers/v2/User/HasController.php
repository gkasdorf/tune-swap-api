<?php

namespace App\Http\Controllers\v2\User;

use App\Helpers\ApiResponse;
use App\Types\SubscriptionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HasController extends \App\Http\Controllers\Controller
{
    public function hasSpotify(Request $request): JsonResponse
    {
        return ApiResponse::success([
            "has" => $request->user()->hasSpotify()
        ]);
    }

    public function hasAppleMusic(Request $request): JsonResponse
    {
        return ApiResponse::success([
            "has" => $request->user()->hasAppleMusic()
        ]);
    }

    public function hasTidal(Request $request): JsonResponse
    {
        return ApiResponse::success([
            "has" => $request->user()->hasTidal()
        ]);
    }

    public function isRunning(Request $request): JsonResponse
    {
        return ApiResponse::success([
            "running" => $request->user()->is_running
        ]);
    }

    public function getActiveSyncCount(Request $request): JsonResponse
    {
        $subscription = $request->user()->getSubscription();

        return ApiResponse::success([
            "message" => "Got total syncs successfully.",
            "total" => $request->user()->getActiveSyncCount(),
            "isTurbo" => $subscription?->subscription_type === SubscriptionType::TURBO
        ]);
    }
}
