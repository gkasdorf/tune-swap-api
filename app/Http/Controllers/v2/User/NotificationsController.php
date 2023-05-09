<?php

namespace App\Http\Controllers\v2\User;

use App\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationsController extends \App\Http\Controllers\Controller
{
    public function enableIos(Request $request): JsonResponse
    {
        if (!isset($request->token))
            return ApiResponse::fail("No token provided.");

        $request->user()->addIosDeviceToken($request->token);

        return ApiResponse::success();
    }

    public function disableIos(Request $request): JsonResponse
    {
        if (!isset($request->token))
            return ApiResponse::fail("No token provided.");

        $request->user()->removeIosDeviceToken($request->token);

        return ApiResponse::success();
    }

    public function iosEnabled(Request $request): JsonResponse
    {
        return ApiResponse::success([
            "enabled" => $request->user()->iosNotificationsEnabled()
        ]);
    }

    public function enableAndroid(Request $request): JsonResponse
    {
        if (!isset($request->token))
            return ApiResponse::fail("No token provided.");

        $request->user()->addAndroidDeviceToken($request->token);

        return ApiResponse::success();
    }

    public function disableAndroid(Request $request): JsonResponse
    {
        if (!isset($request->token))
            return ApiResponse::fail("No token provided.");

        $request->user()->removeAndroidDeviceToken($request->token);

        return ApiResponse::success();
    }

    public function androidEnabled(Request $request): JsonResponse
    {
        return ApiResponse::success([
            "enabled" => $request->user()->androidNotificationsEnabled()
        ]);
    }
}
