<?php

namespace App\Http\Controllers\v2\User;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\PersonalAccessToken;

class LoginController extends Controller
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
                    "api_token" => $token->plainTextToken,
                    "subscribed" => $user->isSubscribed()
                ]
            ]);
        } catch (Exception) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }

    /**
     * Verify a token
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
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

            error_log($user->isSubscribed());

            return ApiResponse::success([
                "user" => [
                    "email" => $user->email,
                    "name" => $user->name,
                    "token" => $token,
                    "subscribed" => $user->isSubscribed()
                ]
            ]);
        } catch (Exception) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }

    public function doAppleAuth(Request $request): JsonResponse
    {
        $code = $request->input("code");
        $name = $request->input("code");

        $data = [
            "client_id" => env("APPLE_CLIENT_ID"),
            "client_secret" => env("APPLE_CLIENT_SECRET"),
            "code" => $code,
            "grant_type" => "authorization_code"
        ];

        try {
            $resp = json_decode(Http::asForm()->post("https://appleid.apple.com/auth/token", $data)->body());
            $payload = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $resp->id_token)[1])))); // 4


            // This is a new signup if we have a name, most likely. We will make sure.
            if ($payload->email && $name) return $this->appleSignUp($payload, $name);
            else if ($payload->email) return $this->appleLogIn($payload);

            return ApiResponse::error("Unable to authenticate with Apple.");
        } catch (Exception) {
            return ApiResponse::error("Unable to authenticate with Apple.");
        }
    }

    private function appleLogIn($payload): JsonResponse
    {
        try {
            $user = User::where("email", $payload->email)->first();

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

    private function appleSignUp($payload, $name): JsonResponse
    {
        try {
            if (User::where("email", $payload->email)->first()) return $this->appleLogIn($payload);


            $user = new User([
                "name" => $name,
                "email" => $payload->email,
                "password" => "APPLE"
            ]);

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
