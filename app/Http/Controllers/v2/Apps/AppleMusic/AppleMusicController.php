<?php

namespace App\Http\Controllers\v2\Apps\AppleMusic;

use App\Api\AppleMusic\AppleMusic;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class AppleMusicController extends Controller
{
    public function getUserPlaylists(Request $request): JsonResponse
    {
        try {
            $am = new AppleMusic($request->user());

            return ApiResponse::success([
                "playlists" => $am->getUserPlaylists()
            ]);
        } catch (Exception) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }

    public function authPage(Request $request): View
    {
        return view("appleMusicAuth", ["apiToken" => $request->input("apiToken")]);
    }

    /**
     * @param Request $request
     * @return Application|\Illuminate\Foundation\Application|JsonResponse|RedirectResponse|Redirector
     */
    public function auth(Request $request): \Illuminate\Foundation\Application|JsonResponse|Redirector|RedirectResponse|Application
    {
        try {
            $user = PersonalAccessToken::findToken(urldecode($request->input("apiToken")))->tokenable;

            $token = str_replace(" ", "+", urldecode($request->input("token")));

            $user->apple_music_token = $token;

            $appleMusic = new AppleMusic($user, $token);

            $storefront = str_replace('"', "", $appleMusic->getUserStorefront()->data[0]->id);

            $user->apple_music_storefront = $storefront;

            $user->save();

            if ($request->input("web")) {
                return redirect(env("CORS_URL") . "/app/user/auth/applemusic?complete=true");
            }

            error_log("Sup");

            return redirect("tuneswap://home/share/AppleMusic?success=true");
        } catch (Exception) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }
}
