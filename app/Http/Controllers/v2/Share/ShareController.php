<?php

namespace App\Http\Controllers\v2\Share;

use App\Helpers\ApiResponse;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\MusicService;
use App\Jobs\DoCopy;
use App\Jobs\PrepareShare;
use App\Models\Copy;
use App\Models\Playlist;
use App\Models\Share;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    public function get(Request $request, $id): JsonResponse
    {
        // Grab it
        $share = Share::where("access_id", $id)
            ->with("playlist")
            ->with(["playlist.user" => function ($query) {
                $query->select("id", "name");
            }])
            ->first();

        // Check if it exists
        if (!$share) {
            return ApiResponse::fail("Share not found.", 404);
        }

        return ApiResponse::success([
            "share" => $share,
            "songCount" => $share->playlist->playlistSongs()->count(),
            "isOwner" => $share->user_id == $request->user()?->id // Send this over so we know if we should have owner UI
        ]);
    }

    public function getAll(Request $request): JsonResponse
    {
        return ApiResponse::success([
            "shares" => $request->user()->shares()->with("playlist")->orderBy("id", "DESC")->get()
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
        } catch (Exception) {
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

        PrepareShare::dispatch($share);

        // Return the share
        return ApiResponse::success([
            "message" => "Share created successfully.",
            "share" => $share
        ]);
    }

    public function delete(Request $request, $id): JsonResponse
    {
        $share = Share::where("access_id", $id)->first();

        if (!$share) {
            return ApiResponse::fail("Share not found.", 404);
        }

        if ($share->user_id != $request->user()->id) {
            return ApiResponse::fail("You do not have permission to delete this share.", 403);
        }

        $share->delete();

        return ApiResponse::success("Share deleted successfully.");
    }


    public function startCopy(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                "service" => "required"
            ]);

            //TODO Add this whenever migration to 1.2 is complete
//            if ($request->user()->is_running) {
//                return ApiResponse::fail("You already have a job running.");
//            }

            $share = Share::where("access_id", $id)->first();

            $copy = new Copy([
                "user_id" => $request->user()->id,
                "share_id" => $share->id,
                "service" => $request->input("service")
            ]);
            $copy->save();

            DoCopy::dispatch($copy, $request->user());

            return ApiResponse::success([
                "message" => "Copy started successfully.",
                "copy" => $copy
            ]);
        } catch (Exception $e) {
            return ApiResponse::error([
                "error" => $e
            ]);
        }
    }

    public function getCopy(string $id): JsonResponse
    {
        try {
            $copy = Copy::where("id", $id)
                ->with("share")
                ->with("share.playlist")
                ->with(["share.playlist.user" => function ($query) {
                    $query->select("id", "name");
                }])
                ->first();

            if (!$copy) {
                return ApiResponse::fail("Copy not found.", 404);
            }

            return ApiResponse::success([
                "copy" => $copy
            ]);
        } catch (Exception $e) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }

    public function getCopies(Request $request): JsonResponse
    {
        try {
            $copies = $request->user()->copies()
                ->with("share")
                ->with("share.playlist")
                ->orderBy("id", "DESC")
                ->get();

            return ApiResponse::success([
                "copies" => $copies
            ]);
        } catch (Exception) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }
}
