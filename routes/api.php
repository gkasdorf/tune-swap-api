<?php

use App\Http\Controllers\SettingsController;
use App\Http\Controllers\v1\AppleMusic\AppleMusicAuthController;
use App\Http\Controllers\v1\AppleMusic\AppleMusicController;
use App\Http\Controllers\v1\Spotify\SpotifyAuthController;
use App\Http\Controllers\v1\Spotify\SpotifyController;
use App\Http\Controllers\v1\SwapController;
use App\Http\Controllers\v1\Tidal\TidalController;
use App\Http\Controllers\v1\UserController;
use App\Http\Controllers\v2\Share\ShareController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


// V2

Route::post("/v2/user/signup", [\App\Http\Controllers\v2\User\SignupController::class, "add"]);
Route::post("/v2/user/login", [\App\Http\Controllers\v2\User\LoginController::class, "check"]);
Route::get("/v2/user/verify", [\App\Http\Controllers\v2\User\LoginController::class, "verify"]);

Route::get("/v2/settings/maintenance", [SettingsController::class, "maintenance"]);

Route::post("/v2/user/login/apple", [\App\Http\Controllers\v2\User\LoginController::class, "doAppleAuth"]);

Route::middleware("auth:sanctum")->group(function () {
    // Has Routes
    Route::get("/v2/user/has/spotify", [\App\Http\Controllers\v2\User\HasController::class, "hasSpotify"]);
    Route::get("/v2/user/has/applemusic", [\App\Http\Controllers\v2\User\HasController::class, "hasAppleMusic"]);
    Route::get("/v2/user/has/tidal", [\App\Http\Controllers\v2\User\HasController::class, "hasTidal"]);

    Route::get("/v2/user/running", [\App\Http\Controllers\v2\User\HasController::class, "isRunning"]);

    // Notification routes
    Route::get("/v2/user/notifications/ios/enable", [\App\Http\Controllers\v2\User\NotificationsController::class, "enableIos"]);
    Route::get("/v2/user/notifications/ios/disable", [\App\Http\Controllers\v2\User\NotificationsController::class, "disableIos"]);
    Route::get("/v2/user/notifications/ios/enabled", [\App\Http\Controllers\v2\User\NotificationsController::class, "iosEnabled"]);

    // Settings routes
    Route::post("/v2/user/settings/name-email", [\App\Http\Controllers\v2\User\SettingsController::class, "updateNameEmail"]);
    Route::post("/v2/user/settings/password", [\App\Http\Controllers\v2\User\SettingsController::class, "updatePassword"]);

    // Delete route
    Route::post("/v2/user/delete", [\App\Http\Controllers\v2\User\DeleteController::class, "delete"]);

    // Swap routes
    Route::post("/v2/swap/start", [\App\Http\Controllers\v2\Swap\SwapController::class, "start"]);
    Route::get("/v2/swap", [\App\Http\Controllers\v2\Swap\SwapController::class, "getAll"]);
    Route::get("/v2/swap/{id}", [\App\Http\Controllers\v2\Swap\SwapController::class, "get"]);
    Route::get("/v2/swap/{id}/notfound", [\App\Http\Controllers\v2\Swap\SwapController::class, "getNotFound"]);

    // Share Routes
    Route::get("/v2/share", [ShareController::class, "getAll"]);
    Route::post("/v2/share/create", [ShareController::class, "add"]);
    Route::get("/v2/share/copy", [ShareController::class, "getCopies"]);

    Route::get("/v2/share/{id}", [ShareController::class, "get"]);
    Route::get("/v2/share/{id}/delete", [ShareController::class, "delete"]);

    Route::post("/v2/share/{id}/copy", [ShareController::class, "startCopy"]);
    Route::get("/v2/share/copy/{id}", [ShareController::class, "getCopy"]);


    // Spotify routes
    Route::get("/v2/spotify/authUrl", [\App\Http\Controllers\v2\Apps\Spotify\SpotifyController::class, "getAuthUrl"]);
    Route::get("/v2/spotify/auth", [\App\Http\Controllers\v2\Apps\Spotify\SpotifyController::class, "auth"]);

    Route::get("/v2/spotify/me/playlists", [\App\Http\Controllers\v2\Apps\Spotify\SpotifyController::class, "getUserPlaylists"]);
    //Route::get("/spotify/me/playlist/{id}", [SpotifyController::class, "playlist"]);
    //Route::get("/spotify/playlist/{id}", [SpotifyController::class, "playlist"]);
    //Route::get("/spotify/me/library", [SpotifyController::class, "library"]);

    Route::get("/v2/applemusic/me/playlists", [\App\Http\Controllers\v2\Apps\AppleMusic\AppleMusicController::class, "getUserPlaylists"]);

    /**
     * Tidal Routes
     */
    Route::get("/v2/tidal/authUrl", [\App\Http\Controllers\v2\Apps\Tidal\TidalController::class, "getAuthUrl"]);
    Route::post("/v2/tidal/auth", [\App\Http\Controllers\v2\Apps\Tidal\TidalController::class, "auth"]);

    Route::get("/v2/tidal/me/playlists", [\App\Http\Controllers\v2\Apps\Tidal\TidalController::class, "getUserPlaylists"]);
});


// V1 ROUTES DEPRECATED

/*
 * User routes
 */

Route::middleware("auth:sanctum")->get("/user/verify", [UserController::class, "verify"]);

Route::post("/user/register", [UserController::class, "store"]);
Route::post("/user/auth", [UserController::class, "auth"]);
Route::get("/user/verify", [UserController:: class, "verify"]);


Route::get("/applemusic/authPage", [AppleMusicAuthController::class, "authPage"]);
Route::get("/applemusic/auth", [AppleMusicAuthController::class, "auth"]);

/*
 * Settings routes
 */
Route::get("/settings/maintenance", [SettingsController::class, "getMaintenance"]);

Route::middleware("auth:sanctum")->group(function () {
    /*
     * User auth routes
     */
    Route::get("/user/has/spotify", [UserController::class, "hasSpotify"]);
    Route::get("/user/has/applemusic", [UserController::class, "hasAppleMusic"]);
    Route::get("/user/has/tidal", [UserController::class, "hasTidal"]);

    Route::get("/user/notifications/ios/enabled", [UserController::class, "iosNotificationsEnabled"]);
    Route::get("/user/notifications/ios/enable", [UserController::class, "enableIosNotifications"]);
    Route::get("/user/notifications/ios/disable", [UserController::class, "disableIosNotifications"]);

    Route::post("/user/delete", [UserController::class, "delete"]);

    /*
     * Spotify auth routes
     */
    Route::get("/spotify/authUrl", [SpotifyAuthController::class, "authUrl"]);
    Route::get("/spotify/auth", [SpotifyAuthController::class, "auth"]);
    Route::get("/spotify/authUrl/mobile", [SpotifyAuthController::class, "authUrlMobile"]);

    /*
     * Spotify user routes
     */
    Route::get("/spotify/me/playlists", [SpotifyController::class, "myPlaylists"]);
    Route::get("/spotify/me/playlist/{id}", [SpotifyController::class, "playlist"]);
    Route::get("/spotify/playlist/{id}", [SpotifyController::class, "playlist"]);
    Route::get("/spotify/me/library", [SpotifyController::class, "library"]);

    /*
     * Apple Music routes
     */
    Route::get("/applemusic/me/library", [AppleMusicController::class, "library"]);
    Route::get("/applemusic/playlist/{id}", [AppleMusicController::class, "playlist"]);
    Route::get("/applemusic/me/playlist/{id}", [AppleMusicController::class, "userPlaylist"]);
    Route::get("/applemusic/me/playlist/{id}/name", [AppleMusicController::class, "userPlaylistName"]);
    Route::get("/applemusic/me/playlists", [AppleMusicController::class, "userPlaylists"]);
    Route::get("/applemusic/me/storefront", [AppleMusicController::class, "storefront"]);

    /**
     * Tidal Routes
     */
    Route::get("/tidal/authUrl", [TidalController::class, "authUrl"]);
    Route::post("/tidal/auth", [TidalController::class, "auth"]);

    Route::get("/tidal/me/playlists", [TidalController::class, "playlists"]);
    Route::get("/tidal/me/playlists/{id}", [TidalController::class, "playlist"]);
    Route::get("/tidal/me/library", [TidalController::class, "library"]);

    /*
     * Swap routes
     */
    Route::post("/swap/start", [SwapController::class, "start"]);
    Route::get("/swap", [SwapController::class, "swaps"]);
    Route::get("/swap/{id}", [SwapController::class, "swap"]);
});
