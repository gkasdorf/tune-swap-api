<?php

namespace App\Http\Controllers\v2\User;

use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class DeleteController extends \App\Http\Controllers\Controller
{
    public function delete(Request $request)
    {
        $request->user()->delete();

        return ApiResponse::success();
    }
}
