<?php

namespace App\Http\Controllers\v2\User;

use App\Helpers\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class LoginController extends \App\Http\Controllers\Controller
{
    /**
     * Authenticate a user
     * @param Request $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            "email" => "required|email",
            "password" => "required"
        ]);

        try {
            $user = User::where("email", $data["email"])->first();

            if (!$user || !Hash::check($data["password"], $user->password)) {
                return ApiResponse::fail("Invalid email or password.");
            }

            $token = $user->createToken("API_TOKEN");

            return ApiResponse::success([
                "data" => [
                    "name" => $user->name,
                    "email" => $user->email,
                    "api_token" => $token->plainTextToken
                ]
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }

    /**
     * Verify a token
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request)
    {
        $token = $request->header("Authorization");

        if (!isset($token)) {
            return ApiResponse::fail("No token provided.", 401);
        }

        $token = explode(" ", $token)[1];

        try {
            $user = PersonalAccessToken::findToken(urldecode($token))->tokenable;

            if (!$user) {
                return ApiResponse::fail("Invalid token.", 401);
            }

            return ApiResponse::success();
        } catch (\Exception $e) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }
}
