<?php

namespace App\Api\Spotify;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

class SpotifyAuthentication
{
    public static function getUrl($customUrl = null): string
    {
        // Set the scopes we want
        $scopes = "user-read-email playlist-read-private user-library-read playlist-modify-private user-library-modify";

        if ($customUrl) {
            $redirect = $customUrl;
        } else {
            $redirect = URL::to("/api/spotify/auth");
        }

        // Create the data to send
        $data = [
            "response_type" => "code",
            "client_id" => env("SPOTIFY_CLIENT_ID"),
            "scope" => $scopes,
            "redirect_uri" => $redirect,
            "state" => "spotify_auth_state"
        ];

        // Create the url
        return "https://accounts.spotify.com/authorize?" . http_build_query($data);
    }

    public static function auth($code, $redirectUrl)
    {
        // Spotify token url
        $url = "https://accounts.spotify.com/api/token";

        // Create the data
        $data = [
            "code" => $code,
            "redirect_uri" => $redirectUrl,
            "grant_type" => "authorization_code"
        ];

        // Create the headers we need
        $headers = [
            "Authorization" => "Basic " . base64_encode(env("SPOTIFY_CLIENT_ID") . ":" . env("SPOTIFY_CLIENT_SECRET")),
        ];

        // Get the response
        $response = Http::withHeaders($headers)->acceptJson()->asForm()->post($url, $data);

        if ($response->clientError()) {
            return false;
        }

        return json_decode($response->body());
    }

    public static function refresh(User $user)
    {
        // Set the url
        $url = "https://accounts.spotify.com/api/token";

        // SEt the data
        $data = [
            "grant_type" => "refresh_token",
            "refresh_token" => $user->spotify_refresh_token
        ];

        // SEt the headers
        $headers = [
            "Authorization" => "Basic " . base64_encode(env("SPOTIFY_CLIENT_ID") . ":" . env("SPOTIFY_CLIENT_SECRET")),
        ];

        // Create the request
        $response = Http::withHeaders($headers)->acceptJson()->asForm()->post($url, $data);

        // Check for an error
        if ($response->clientError()) {
            return null;
        }

        // Decode
        $result = json_decode($response->body());

        // Update the user
        $user->spotify_token = $result->access_token;
        $user->spotify_expiration = $result->expires_in + time();

        $user->save();

        // Return the new token
        return $user->spotify_token;
    }
}
