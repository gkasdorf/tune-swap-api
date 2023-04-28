<?php

namespace App\Http\Controllers\v2\Share;

use App\Helpers\ApiResponse;
use App\Helpers\Helpers;
use App\Http\MusicService;
use App\Models\Playlist;
use App\Models\Share;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShareController extends \App\Http\Controllers\Controller
{
    public function get(Request $request, $id): JsonResponse
    {
        // Grab it
        $share = Share::where("access_id", $id)->with("playlist")->with("playlist.playlistSongs")->first();

        // Check if it exists
        if (!$share) {
            return ApiResponse::fail("Share not found.", 404);
        }

        return ApiResponse::success([
            "share" => $share,
            "isOwner" => $share->user_id == $request->user()->id // Send this over so we know if we should have owner UI
        ]);
    }

    public function add(Request $request): JsonResponse
    {
        // Validate the data
        $data = $request->validate([
            "playlist_service" => "required|string",
            "playlist_id" => "required|string"
        ]);

        $playlistName = null;

        // Try to set the playlist name
        try {
            $playlistName = Helpers::serviceToApi(MusicService::from($data["playlist_service"]), $request->user())->getPlaylistName($data["playlist_id"]);
        } catch (\Exception $e) {
        }

        // Create a playlist
        $playlist = new Playlist([
            "name" => $playlistName ?? "Unknown",
            "user_id" => $request->user()->id,
            "service" => $data["playlist_service"],
            "service_id" => $data["playlist_id"]
        ]);
        $playlist->save();

        // Create the share
        $share = new Share([
            "access_id" => Helpers::generateUniqueId(),
            "playlist_id" => $playlist->id
        ]);
        $share["user_id"] = $request->user()->id;
        $share->save();

        // Return the share
        return ApiResponse::success([
            "message" => "Share created successfully.",
            "share" => $share
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $share = Share::where("access_id", $request->input("id"))->first();

        if (!$share) {
            return ApiResponse::fail("Share not found.", 404);
        }

        if ($share->user_id != $request->user()->id) {
            return ApiResponse::fail("You do not have permission to delete this share.", 403);
        }

        $share->delete();

        return ApiResponse::success("Share deleted successfully.");
    }
}
