<?php

namespace App\Http\Controllers\v2\Swap;

use App\Helpers\ApiResponse;
use App\Jobs\ProcessSwap;
use App\Models\Swap;
use App\Models\SwapStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SwapController
{
    public function start(Request $request): JsonResponse
    {
        try {
            $request->validate([
                "from_service" => "required",
                "to_service" => "required",
                "from_playlist_id" => "required",
                "playlist_name" => "required",
                "description" => "nullable|string"
            ]);

            $data["user_id"] = $request->user()->id;
            $data["status"] = SwapStatus::CREATED;

            $swap = new Swap($data);
            $swap->save();

            ProcessSwap::dispatch($request->user(), $swap);

            return ApiResponse::success([
                "swapId" => $swap->id,
                "swapStatus" => $swap->status
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }

    public function getAll(Request $request): JsonResponse
    {
        try {
            $limit = $request->limit ?? 1000;
            $offset = $request->offset ?? 0;

            $swaps = $request->user()->swaps();

            return ApiResponse::success([
                "total" => count($swaps),
                "swaps" => count($swaps) > 0 ? $swaps : []
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }

    public function get(Request $request, $id): JsonResponse
    {
        try {
            $swap = Swap::getSwap($id);

            if (!$swap) {
                return ApiResponse::fail("Swap not found.", 404);
            }

            if ($swap->user_id !== $request->user()->id) {
                return ApiResponse::fail("You do not have permission to view this swap.", 401);
            }

            return ApiResponse::success([
                "swap" => $swap
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }
}
