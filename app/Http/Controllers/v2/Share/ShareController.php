<?php

namespace App\Http\Controllers\v2\Share;

use App\Helpers\ApiResponse;
use App\Helpers\Helpers;
use App\Helpers\UniqueId;
use App\Http\MusicService;
use App\Models\Playlist;
use App\Models\Share;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShareController extends \App\Http\Controllers\Controller
{
    public function get(Request $request): JsonResponse
    {
        $share = Share::where("access_id", $request->input("id"))->first();

        if (!$share) {
            return ApiResponse::fail("Share not found.", 404);
        }

        return ApiResponse::success([
            "share" => $share,
            "isOwner" => $share->user_id == $request->user()->id
        ]);
    }

    public function add(Request $request): JsonResponse
    {
        $data = $request->validate([
            "playlist_service" => "required|string",
            "playlist_id" => "required|string"
        ]);

        $playlistName = null;

        try {
            $playlistName = Helpers::serviceToApi(MusicService::from($data["playlist_service"]), $request->user())->getPlaylistName($data["playlist_id"]);
        } catch (\Exception $e) {
        }


        $playlist = new Playlist([
            "name" => $playlistName ?? "Unknown",
            "user_id" => $request->user()->id,
            "service" => $data["playlist_service"],
            "service_id" => $data["playlist_id"]
        ]);
        $playlist->save();

        $share = new Share([
            "user_id" => $request->user()->id,
            "access_id" => Helpers::generateUniqueId(),
            "playlist_id" => $playlist->id
        ]);
        $share->save();

        return ApiResponse::success([
            "message" => "Share created successfully.",
            "share" => $share
        ]);
    }
}
