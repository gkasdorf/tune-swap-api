<?php

namespace App\Http\Controllers\v2\User;

use App\Helpers\ApiResponse;
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
}
