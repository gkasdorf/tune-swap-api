<?php

namespace App\Http\Controllers\v1\AppleMusic;

use App\Api\AppleMusic\AppleMusic;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AppleMusicAuthController extends Controller
{
    public function authPage(Request $request)
    {
        return view("appleMusicAuth", ["apiToken" => $request->apiToken]);
    }

    public function auth(Request $request)
    {
        /** @var User $user */
        $user = PersonalAccessToken::findToken(urldecode($request->apiToken))->tokenable;

        $token = str_replace(" ", "+", urldecode($request->token));

        $user->apple_music_token = $token;

        $appleMusic = new AppleMusic($user, $token);

        $storefront = str_replace('"', "", $appleMusic->getUserStorefront()->data[0]->id);

        $user->apple_music_storefront = $storefront;

        $user->save();

        if ($request->web) {
            return redirect(env("CORS_URL") . "/app/applemusic/auth?complete=true");
        }

        return redirect("tuneswap://home?success=true");
    }
}
