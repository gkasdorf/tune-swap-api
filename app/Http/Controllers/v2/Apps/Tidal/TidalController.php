<?php

namespace App\Http\Controllers\v2\Apps\Tidal;

use App\Api\Tidal\Tidal;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TidalController extends Controller
{
    public function getAuthUrl(Request $request): JsonResponse
    {
        $android = $request->input("android");

        try {
            return ApiResponse::success([
                "url" => Tidal::createAuthUrl($android == "true")
            ]);
        } catch (Exception) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }

    public function auth(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $code = $request->input("code");
            $codeVerifier = $request->input("codeVerifier");
            $android = $request->input("android") == "true";

            $response = Tidal::auth($code, $codeVerifier, $android);

            if (!$response) {
                return ApiResponse::error("There was an error authenticating with Tidal.");
            }

            $user->tidal_token = $response->access_token;
            $user->tidal_expiration = $response->expires_in + time();
            $user->tidal_refresh_token = $response->refresh_token;
            $user->tidal_email = $response->user->email;
            $user->tidal_username = $response->user->username;
            $user->tidal_user_id = $response->user->userId;

            $user->save();

            return ApiResponse::success([
                "email" => $user->tidal_email,
                "username" => $user->tidal_username
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            error_log($e->getLine());
            error_log($e->getCode());
            error_log($e->getTraceAsString());
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }

    public function getUserPlaylists(Request $request): JsonResponse
    {
        try {
            $tidal = new Tidal($request->user());

            return ApiResponse::success([
                "playlists" => $tidal->getUserPlaylists()
            ]);
        } catch (Exception) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }
}
