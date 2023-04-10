<?php

namespace App\Http\Controllers\Tidal;

use App\Http\Controllers\Controller;
use App\Tidal\Tidal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TidalController extends Controller
{
    /**
     * @param Request $request
     * @return array
     */
    public function authUrl(Request $request)
    {
        return [
            "code" => 1000,
            "message" => "User is authorized, URL created",
            "data" => Tidal::createAuthUrl()
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function auth(Request $request): array
    {
        $user = $request->user();

        $code = $request->code;
        $codeVerifier = $request->codeVerifier;

        error_log($code);
        error_log($codeVerifier);

        $response = Tidal::auth($code, $codeVerifier);

        if (!$response) {
            return [
                "code" => 2000,
                "message" => "There was an error authenticating with Tidal."
            ];
        }

        error_log(json_encode($response));

        $user->tidal_token = $response->access_token;
        $user->tidal_expiration = $response->expires_in + time();
        $user->tidal_refresh_token = $response->refresh_token;
        $user->tidal_email = $response->user->email;
        $user->tidal_username = $response->user->username;
        $user->tidal_user_id = $response->user->userId;

        $user->save();

        return [
            "code" => 1000,
            "message" => "Tidal successfully authenticated.",
            "data" => [
                "tidalEmail" => $user->tidal_email,
                "tidalUsername" => $user->tidal_username
            ]
        ];
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function playlists(Request $request): JsonResponse
    {
        $tidal = new Tidal($request->user());

        return response()->json($tidal->getUserPlaylists());
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function playlist(Request $request, $id): JsonResponse
    {
        $tidal = new Tidal($request->user());

        return response()->json($tidal->getPlaylist($id));
    }

    public function library(Request $request): JsonResponse
    {
        $tidal = new Tidal($request->user());

        return response()->json($tidal->getLibrary());
    }
}
