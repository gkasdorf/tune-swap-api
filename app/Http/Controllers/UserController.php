<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class UserController extends Controller
{
    /**
     * Create a new user
     */
    public function store(Request $request)
    {
        $user = new User;

        $user->name = $request->input("name");
        $user->email = $request->input("email");
        $user->password = $request->input("password");
        $passwordAgain = $request->input("passwordAgain");

        if (!$user->email || !$user->name || !$user->password) {
            return [
                "code" => 2000,
                "message" => "Name, email, and password are required"
            ];
        }

        // Check the password matches
        if ($user->password != $passwordAgain) {
            return [
                "code" => 2001,
                "message" => "Passwords must match"
            ];
        }

        // Make sure the email isn't already registered
        if (User::where('email', $user->email)->count()) {
            return [
                "code" => 2002,
                "message" => "Email is already in use"
            ];
        }

        try {
            // Hash the password
            $user->password = Hash::make($user->password);

            // Save the new user
            $user->save();

            // Create an api token
            $token = $user->createToken("API_TOKEN");

            // Return the success
            return [
                "code" => 1000,
                "message" => "User successfully registered",
                "data" => [
                    "name" => $user->name,
                    "email" => $user->email,
                    "api_token" => $token->plainTextToken,
                    "spotify" => false,
                    "spotifyEmail" => null,
                    "appleMusic" => false
                ]
            ];
        } catch (\Exception $e) {
            print($e);

            return [
                "code" => 3000,
                "message" => "An unexpected error has occurred."
            ];
        }
    }

    /**
     * Authenticate a user
     */
    public function auth(Request $request)
    {
        $email = $request->email;
        $password = $request->password;

        // See if the user exists
        try {
            $user = User::where("email", $email)->firstOrFail();
        } catch (\Exception $e) {
            return [
                "code" => 2000,
                "message" => "User does not exist"
            ];
        }


        // Check that the password is correct

        if (!Hash::check($password, $user->password)) {
            return [
                "code" => 2001,
                "message" => "Password is incorrect"
            ];
        }

        // Create the API token
        $token = $user->createToken("API_TOKEN");

        // Return the success
        return [
            "code" => 1000,
            "message" => "User successfully authenticated",
            "data" => [
                "name" => $user->name,
                "email" => $user->email,
                "api_token" => $token->plainTextToken,
                "spotify" => isset($user->spotify_token),
                "spotifyEmail" => $user->spotify_email,
                "appleMusic" => isset($user->apple_music_token)
            ]
        ];
    }

    public function verify(Request $request)
    {
        $apiToken = $request->header("Authorization");

        if (!isset($apiToken)) {
            return [
                "code" => 2000,
                "message" => "User not authenticated."
            ];
        }

        $apiToken = explode(" ", $apiToken)[1];

        try {
            $user = PersonalAccessToken::findToken(urldecode($apiToken))->tokenable;

            if (!$user) {
                return [
                    "code" => 2000,
                    "message" => "User not authenticated."
                ];
            }

            return [
                "code" => 1000,
                "message" => "User is authenticated"
            ];
        } catch (\Exception) {
            return [
                "code" => 2000,
                "message" => "User not authenticated."
            ];
        }
    }

    public function hasSpotify(Request $request)
    {
        return [
            "code" => 1000,
            "message" => "User authenticated. See auth.",
            "has" => $request->user()->hasSpotify()
        ];
    }

    public function hasAppleMusic(Request $request)
    {
        return [
            "code" => 1000,
            "message" => "User authenticated. See auth.",
            "has" => $request->user()->hasAppleMusic()
        ];
    }

    public function hasTidal(Request $request)
    {
        return [
            "code" => 1000,
            "message" => "User authenticated. See auth.",
            "has" => $request->user()->hasTidal()
        ];
    }

    public function enableIosNotifications(Request $request)
    {
        $request->user()->addIosDeviceToken($request->token);

        return [
            "code" => 1000,
            "message" => "Notifications enabled."
        ];
    }

    public function disableIosNotifications(Request $request)
    {
        $request->user()->removeIosDeviceToken($request->token);

        return [
            "code" => 1000,
            "message" => "Notifications disabled."
        ];
    }

    public function iosNotificationsEnabled(Request $request)
    {
        return [
            "code" => 1000,
            "message" => "See `enabled`",
            "enabled" => $request->user()->iosNotificationsEnabled()
        ];
    }

    public function delete(Request $request)
    {
        if (!$request->password || !Hash::check($request->password, $request->user()->password)) {
            return [
                "code" => 2001,
                "message" => "Password is incorrect"
            ];
        }

        $request->user()->delete();

        return [
            "code" => 1000,
            "message" => "User deleted."
        ];
    }
}
