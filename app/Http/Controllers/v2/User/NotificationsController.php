<?php

namespace App\Http\Controllers\v2\User;

use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class NotificationsController extends \App\Http\Controllers\Controller
{
    public function enableIos(Request $request)
    {
        if (!isset($request->token))
            return ApiResponse::fail("No token provided.");

        $request->user()->addIosDeviceToken($request->token);

        return ApiResponse::success();
    }

    public function disableIos(Request $request)
    {
        if (!isset($request->token))
            return ApiResponse::fail("No token provided.");

        $request->user()->removeIosDeviceToken($request->token);

        return ApiResponse::success();
    }

    public function iosEnabled(Request $request)
    {
        return ApiResponse::success([
            "enabled" => $request->user()->iosNotificationsEnabled()
        ]);
    }
}