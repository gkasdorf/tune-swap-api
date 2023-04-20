<?php

namespace App\Http\Controllers\v2\User;

use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DeleteController extends \App\Http\Controllers\Controller
{
    public function delete(Request $request)
    {
        $request->validate([
            "password" => "required"
        ]);

        if (!Hash::check($request->password, $request->user()->password)) {
            return ApiResponse::fail("Incorrect password.", 401);
        }

        $request->user()->delete();

        return ApiResponse::success();
    }
}
