<?php

namespace App\Http\Controllers\v2\User;

use App\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteController extends \App\Http\Controllers\Controller
{
    public function delete(Request $request): JsonResponse
    {
        $request->user()->delete();

        return ApiResponse::success();
    }
}
