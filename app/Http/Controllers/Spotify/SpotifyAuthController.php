<?php

namespace App\Http\Controllers\Spotify;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Spotify\Spotify;
use App\Spotify\SpotifyAuthentication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class SpotifyAuthController extends Controller
{
    public function authUrl(Request $request)
    {
        if ($request->redirect_url) {
            $url = SpotifyAuthentication::getUrl($request->redirect_url);
        } else {
            $url = SpotifyAuthentication::getUrl();
        }

        return [
            "code" => 1000,
            "message" => "User is authorized, URL created",
            "url" => $url
        ];
    }

    public function auth(Request $request)
    {
        // Get the user from the database
        $user = User::where("email", $request->user()->email)->first();

        // Get the code that was sent with the request
        $code = $request->code;

        if ($request->redirect_url) {
            $redirectUrl = $request->redirect_url;
        } else {
            $redirectUrl = URL::to("/api/spotify/auth");
        }

        // Get the response
        $response = SpotifyAuthentication::auth($code, $redirectUrl);

        // Make sure there was not an error
        if (!$response) {
            return [
                "code" => 2000,
                "message" => "There was an error authenticating with Spotify"
            ];
        }

        // Set the values
        $user->spotify_token = $response->access_token;
        $user->spotify_expiration = $response->expires_in + time();
        $user->spotify_refresh_token = $response->refresh_token;

        $spotify = new Spotify($user);
        $profile = $spotify->getProfile();

        $user->spotify_email = $profile["email"];
        $user->spotify_user_id = $profile["id"];

        // Update
        $user->save();

        return [
            "code" => 1000,
            "message" => "Spotify successfully authenticated",
            "email" => $profile["email"]
        ];
    }
}
