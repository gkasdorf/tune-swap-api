<?php

namespace App\Http\Controllers\v2\Sync;

use App\Helpers\ApiResponse;
use App\Helpers\Helpers;
use App\Models\Playlist;
use App\Models\Sync;
use App\Types\MusicService;
use Exception;
use Faker\Core\DateTime;
use Illuminate\Http\Request;

class SyncController extends \App\Http\Controllers\Controller
{
    public function create(Request $request)
    {
        // Validate the data
        $request->validate([
            "fromService" => "required",
            "fromId" => "required",
            "toService" => "required",
            "toId" => "required",
        ]);


        // Create the APIs
        $fromApi = null;
        $toApi = null;

        try {
            $fromApi = Helpers::serviceToApi(MusicService::from($request->input("fromService")), $request->user());
            $toApi = Helpers::serviceToApi(MusicService::from($request->input("toApi")), $request->user());
        } catch (Exception $e) {
            return ApiResponse::fail("Invalid service type.");
        }

        // Get the playlist names
        try {
            $fromName = $fromApi->getPlaylistName($request->input("fromId"));
            $toName = $fromApi->getPlaylistName($request->input("toId"));
        } catch (Exception $e) {
            return ApiResponse::fail("Invalid playlist ID.");
        }

        // Create the playlists
        $fromPlaylist = new Playlist([
            "user_id" => $request->user()->id,
            "name" => $fromName,
            "service" => $request->input("fromService"),
            "service_id" => $request->input("fromId")
        ]);
        $fromPlaylist->save();

        $toPlaylist = new Playlist([
            "user_id" => $request->user()->id,
            "name" => $toName,
            "service" => $request->input("toService"),
            "service_id" => $request->input("toId")
        ]);
        $toPlaylist->save();

        // Create the sync
        $sync = new Sync([
            "user_id" => $request->user()->id,
            "from_playlist_id" => $fromPlaylist->id,
            "to_playlist_id" => $toPlaylist->id,
            "last_updated" => new DateTime(),
            "syncing" => true
        ]);
        $sync->save();

        return ApiResponse::success([
            "message" => "Sync created successfully.",
            "sync" => $sync
        ]);
    }
}
