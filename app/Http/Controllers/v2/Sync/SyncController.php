<?php

namespace App\Http\Controllers\v2\Sync;

use App\Helpers\ApiResponse;
use App\Jobs\DoSync;
use App\Models\Sync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends \App\Http\Controllers\Controller
{
    public function getAll(Request $request): JsonResponse
    {
        try {
            return ApiResponse::success([
                "syncs" => $request
                    ->user()
                    ->syncs()
                    ->with("fromPlaylist")
                    ->with("toPlaylist")
                    ->orderBy("id", "DESC")
                    ->get()
            ]);
        } catch (\Exception $e) {
            return ApiResponse::fail($e->getMessage(), 500);
        }
    }

    public function get(Request $request, $id): JsonResponse
    {
        $sync = $request->user()
            ->syncs()
            ->where("id", $id)
            ->with("fromPlaylist")
            ->with("toPlaylist")
            ->first();

        if (!$sync) {
            return ApiResponse::fail("Sync not found.", 404);
        }

        return ApiResponse::success([
            "sync" => $sync
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        // Validate the data
        $request->validate([
            "fromService" => "required",
            "fromId" => "required",
            "toService" => "required",
            "toId" => "required",
        ]);

        // Create the sync
        $sync = new Sync([
            "user_id" => $request->user()->id,
            "syncing" => true
        ]);
        $sync->save();

        DoSync::dispatch($sync, [
            "fromService" => $request->input("fromService"),
            "fromId" => $request->input("fromId"),
            "toService" => $request->input("toService"),
            "toId" => $request->input("toId")
        ]);

        return ApiResponse::success([
            "message" => "Sync created successfully.",
            "sync" => $sync
        ]);
    }
}
