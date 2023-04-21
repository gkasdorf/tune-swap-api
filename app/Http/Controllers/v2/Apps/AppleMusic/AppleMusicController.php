<?php

namespace App\Http\Controllers\v2\Apps\AppleMusic;

use App\Api\AppleMusic\AppleMusic;
use App\Helpers\ApiResponse;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Laravel\Sanctum\PersonalAccessToken;

class AppleMusicController extends \App\Http\Controllers\Controller
{
    public function getUserPlaylists(Request $request): JsonResponse
    {
        try {
            $am = new AppleMusic($request->user());

            return ApiResponse::success([
                "playlists" => $am->getUserPlaylists()
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }

    public function authPage(Request $request): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\Factory|Application
    {
        return view("appleMusicAuth", ["apiToken" => $request->apiToken]);
    }

    /**
     * @param Request $request
     * @return Application|\Illuminate\Foundation\Application|JsonResponse|RedirectResponse|Redirector
     */
    public function auth(Request $request): \Illuminate\Foundation\Application|JsonResponse|Redirector|RedirectResponse|Application
    {
        try {
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
        } catch (\Exception $e) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }
}
