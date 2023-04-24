<?php

namespace App\Http\Controllers\v2\User;

use App\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController
{
    /**
     * Update a user's password
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            "password" => "required",
            "newPassword" => "required",
            "newPasswordConfirmed" => "required|same:newPassword"
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return ApiResponse::fail("Invalid password.", 401);
        }

        $user->password = Hash::make($request->newPassword);
        $user->save();

        return ApiResponse::success("Password has been updated.");
    }

    /**
     * Update a user's name or email
     * @param Request $request
     * @return JsonResponse
     */
    public function updateNameEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            "name" => "required",
            "email" => "required|unique:users,email",
            "password" => "required"
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return ApiResponse::fail("Invalid password.", 401);
        }

        $user->name = $request->name;
        $user->email = $request->email;

        return ApiResponse::success("User has been updated.");
    }
}
