<?php

use App\Http\Controllers\SettingsController;
use App\Http\Controllers\v2\Apps\AppleMusic\AppleMusicController;
use App\Http\Controllers\v2\Apps\Spotify\SpotifyController;
use App\Http\Controllers\v2\Apps\Tidal\TidalController;
use App\Http\Controllers\v2\Share\ShareController;
use App\Http\Controllers\v2\Swap\SwapController;
use App\Http\Controllers\v2\User\DeleteController;
use App\Http\Controllers\v2\User\HasController;
use App\Http\Controllers\v2\User\LoginController;
use App\Http\Controllers\v2\User\NotificationsController;
use App\Http\Controllers\v2\User\SignupController;
use App\Http\Controllers\v2\User\SubscriptionController;
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

Route::post("/v2/user/signup", [SignupController::class, "add"]);
Route::post("/v2/user/login", [LoginController::class, "check"]);
Route::get("/v2/user/verify", [LoginController::class, "verify"]);

Route::get("/v2/settings/maintenance", [SettingsController::class, "getMaintenance"]);

Route::post("/v2/user/login/apple", [LoginController::class, "doAppleAuth"]);


Route::get("/applemusic/authPage", [AppleMusicController::class, "authPage"]);
Route::get("/applemusic/auth", [AppleMusicController::class, "auth"]);

Route::middleware("auth:sanctum")->group(function () {
    // Has Routes
    Route::get("/v2/user/has/spotify", [HasController::class, "hasSpotify"]);
    Route::get("/v2/user/has/applemusic", [HasController::class, "hasAppleMusic"]);
    Route::get("/v2/user/has/tidal", [HasController::class, "hasTidal"]);

    Route::get("/v2/user/running", [HasController::class, "isRunning"]);

    // Subscription routes
    Route::get("/v2/user/subscription", [SubscriptionController::class, "getSubscription"]);

    Route::post("/v2/user/subscription/verify/apple", [SubscriptionController::class, "verifySubscriptionIos"]);

    // Notification routes
    Route::get("/v2/user/notifications/ios/enable", [NotificationsController::class, "enableIos"]);
    Route::get("/v2/user/notifications/ios/disable", [NotificationsController::class, "disableIos"]);
    Route::get("/v2/user/notifications/ios/enabled", [NotificationsController::class, "iosEnabled"]);

    Route::get("/v2/user/notifications/android/enable", [NotificationsController::class, "enableAndroid"]);
    Route::get("/v2/user/notifications/android/disable", [NotificationsController::class, "enableIos"]);
    Route::get("/v2/user/notifications/android/enabled", [NotificationsController::class, "androidEnabled"]);

    // Settings routes
    Route::post("/v2/user/settings/name-email", [\App\Http\Controllers\v2\User\SettingsController::class, "updateNameEmail"]);
    Route::post("/v2/user/settings/password", [\App\Http\Controllers\v2\User\SettingsController::class, "updatePassword"]);

    // Delete route
    Route::post("/v2/user/delete", [DeleteController::class, "delete"]);

    // Swap routes
    Route::post("/v2/swap/start", [SwapController::class, "start"]);
    Route::get("/v2/swap", [SwapController::class, "getAll"]);
    Route::get("/v2/swap/{id}", [SwapController::class, "get"]);
    Route::get("/v2/swap/{id}/notfound", [SwapController::class, "getNotFound"]);

    // Share Routes
    Route::get("/v2/share", [ShareController::class, "getAll"]);
    Route::post("/v2/share/create", [ShareController::class, "add"]);
    Route::get("/v2/share/copy", [ShareController::class, "getCopies"]);

    Route::get("/v2/share/{id}/delete", [ShareController::class, "delete"]);

    Route::post("/v2/share/{id}/copy", [ShareController::class, "startCopy"]);
    Route::get("/v2/share/copy/{id}", [ShareController::class, "getCopy"]);

    Route::post("/v2/sync/create", [\App\Http\Controllers\v2\Sync\SyncController::class, "create"]);

    // Spotify routes
    Route::get("/v2/spotify/authUrl", [SpotifyController::class, "getAuthUrl"]);
    Route::get("/v2/spotify/auth", [SpotifyController::class, "auth"]);

    Route::get("/v2/spotify/me/playlists", [SpotifyController::class, "getUserPlaylists"]);

    Route::get("/v2/applemusic/me/playlists", [AppleMusicController::class, "getUserPlaylists"]);

    /**
     * Tidal Routes
     */
    Route::get("/v2/tidal/authUrl", [TidalController::class, "getAuthUrl"]);
    Route::post("/v2/tidal/auth", [TidalController::class, "auth"]);

    Route::get("/v2/tidal/me/playlists", [TidalController::class, "getUserPlaylists"]);
});

Route::get("/v2/share/{id}", [ShareController::class, "get"]);
/*
 * Settings routes
 */
Route::get("/settings/maintenance", [SettingsController::class, "getMaintenance"]);

