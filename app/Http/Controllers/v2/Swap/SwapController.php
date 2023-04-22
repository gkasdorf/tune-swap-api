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
        $data = $request->validate([
            "from_service" => "required",
            "to_service" => "required",
            "from_playlist_id" => "required",
            "playlist_name" => "required",
            "description" => "nullable|string"
        ]);

        try {
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
            error_log($e->getMessage());
            error_log($e->getLine());
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }

    public function getAll(Request $request): JsonResponse
    {
        try {
            $limit = $request->limit ?? 1000;
            $offset = $request->offset ?? 0;

            $swaps = $request->user()->swaps()->orderBy('id', 'DESC')->get();

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

    public function getNotFound(Request $request, $id): JsonResponse
    {
        try {
            $notFound = Swap::where("id", $id)->with("songsNotFound.song")->first();

            if (!$notFound) {
                return ApiResponse::fail("Swap not found.", 404);
            }

            if ($notFound->user_id !== $request->user()->id) {
                return ApiResponse::fail("You do not have permission to view this swap.", 401);
            }

            return ApiResponse::success([
                "swap" => $notFound
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error("An unexpected error has occurred.");
        }
    }
}