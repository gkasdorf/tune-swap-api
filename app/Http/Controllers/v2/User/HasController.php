<?php

namespace App\Http\Controllers\v2\User;

use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class HasController extends \App\Http\Controllers\Controller
{
    public function hasSpotify(Request $request)
    {
        return ApiResponse::success([
            "has" => $request->user()->hasSpotify()
        ]);
    }

    public function hasAppleMusic(Request $request)
    {
        return ApiResponse::success([
            "has" => $request->user()->hasAppleMusic()
        ]);
    }

    public function hasTidal(Request $request)
    {
        return ApiResponse::success([
            "has" => $request->user()->hasTidal()
        ]);
    }
}
