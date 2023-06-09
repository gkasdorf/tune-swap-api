<?php

namespace App\Http\Controllers\v2\Apps\Spotify;

use App\Api\Spotify\Spotify;
use App\Api\Spotify\SpotifyAuthentication;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class SpotifyController extends Controller
{
    public function getAuthUrl(Request $request): JsonResponse
    {
        try {
            $url = SpotifyAuthentication::getUrl($request->redirect_uri ?? null);

            return ApiResponse::success([
                "url" => $url
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
            $redirectUrl = $request->redirect_uri ?? URL::to("/api/spotify/auth");

            $resp = SpotifyAuthentication::auth($code, $redirectUrl);

            if (!$resp) {
                return ApiResponse::error("There was an error authenticating with Spotify.");
            }

            $user->spotify_token = $resp->access_token;
            $user->spotify_expiration = $resp->expires_in + time();
            $user->spotify_refresh_token = $resp->refresh_token;

            $spotify = new Spotify($user);
            $profile = $spotify->getProfile();

            $user->spotify_email = $profile["email"];
            $user->spotify_user_id = $profile["id"];

            $user->save();

            return ApiResponse::success([
                "email" => $profile["email"]
            ]);
        } catch (Exception) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }

    public function getUserPlaylists(Request $request): JsonResponse
    {
        try {
            $spotify = new Spotify($request->user());

            return ApiResponse::success([
                "playlists" => $spotify->getUserPlaylists()
            ]);
        } catch (Exception) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }
}
