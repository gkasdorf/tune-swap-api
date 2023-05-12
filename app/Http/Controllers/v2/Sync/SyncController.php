<?php

namespace App\Http\Controllers\v2\Sync;

use App\Helpers\ApiResponse;
use App\Jobs\DoSync;
use App\Models\Sync;
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
