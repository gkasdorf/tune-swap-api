<?php

use App\Http\Controllers\AppleMusic\AppleMusicAuthController;
use App\Http\Controllers\AppleMusic\AppleMusicController;
use App\Http\Controllers\Spotify\SpotifyAuthController;
use App\Http\Controllers\Spotify\SpotifyController;
use App\Http\Controllers\SwapController;
use App\Http\Controllers\Tidal\TidalController;
use App\Http\Controllers\UserController;
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


/*
 * User routes
 */
Route::middleware("auth:sanctum")->get("/user/verify", [UserController::class, "verify"]);

Route::post("/user/register", [UserController::class, "store"]);
Route::post("/user/auth", [UserController::class, "auth"]);
Route::get("/user/verify", [UserController:: class, "verify"]);


Route::get("/applemusic/authPage", [AppleMusicAuthController::class, "authPage"]);
Route::get("/applemusic/auth", [AppleMusicAuthController::class, "auth"]);

Route::middleware("auth:sanctum")->group(function () {
    /*
     * User auth routes
     */
    Route::get("/user/has/spotify", [UserController::class, "hasSpotify"]);
    Route::get("/user/has/applemusic", [UserController::class, "hasAppleMusic"]);
    Route::get("/user/has/tidal", [UserController::class, "hasTidal"]);

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

    Route::get("/tidal/me/playlists", [TidalController::class, "getUserPlaylists"]);
    Route::get("/tidal/me/playlists/{id}", [TidalController::class, "getPlaylist"]);

    /*
     * Swap routes
     */
    Route::post("/swap/start", [SwapController::class, "start"]);
    Route::get("/swap", [SwapController::class, "swaps"]);
    Route::get("/swap/{id}", [SwapController::class, "swap"]);
});
