<?php

namespace App\Http\Controllers\v2\User;

use App\Helpers\ApiResponse;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SignupController extends \App\Http\Controllers\Controller
{
    /**
     * Add a new user
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request): JsonResponse
    {
        $data = $request->validate([
            "name" => "required",
            "email" => "required|email|unique:users,email",
            "password" => "required",
            "passwordAgain" => "required|same:password"
        ]);

        try {
            $data["password"] = Hash::make($data["password"]);
            $user = new User($data);

            $user->save();

            $token = $user->createToken("API_TOKEN");

            return ApiResponse::success([
                "data" => [
                    "name" => $user->name,
                    "email" => $user->email,
                    "api_token" => $token->plainTextToken
                ]
            ]);
        } catch (Exception) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }
}
