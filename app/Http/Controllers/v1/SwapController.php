<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSwapOld;
use App\Models\Swap;
use App\Models\SwapStatus;
use Illuminate\Http\Request;

class SwapController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Start a submitted swap
     * @param Request $request
     * @return array
     */
    public function start(Request $request): array
    {
        // Create the swap
        $swap = new Swap();

        // Set the data
        $swap->user_id = $request->user()->id;
        $swap->from_service = $request->fromService;
        $swap->to_service = $request->toService;
        $swap->from_playlist_id = $request->playlistId;
        $swap->playlist_name = $request->playlistName;
        $swap->status = SwapStatus::CREATED;
        $swap->description = $request->playlistDescription;

        // Save the swap
        $swap->save();

        // Dispatch the swap
        ProcessSwapOld::dispatch($request->user(), $swap);

        // Return the swap information
        return [
            "code" => 1000,
            "message" => "Swap successfully created",
            "data" => [
                "swapId" => $swap->id,
                "swapStatus" => $swap->status,
            ]
        ];
    }

    /** Return all the swaps to the user
     * @param Request $request
     * @return array
     */
    public function swaps(Request $request): array
    {
        $limit = $request->limit ? $request->limit : 1000;
        $offset = $request->offset ? $request->offset : 0;

        $swaps = Swap::getSwaps($request->user()->id, $limit, $offset);

        return [
            "code" => 1000,
            "message" => "Selected swaps successfully",
            "data" => [
                "total" => count($swaps),
                "swaps" => count($swaps) > 0 ? $swaps : []
            ]
        ];
    }

    public function swap(Request $request, $id): array
    {
        $swap = Swap::getSwap($id);

        if ($swap->user_id !== $request->user()->id)
            $swap = null;

        if ($swap == null) {
            return [
                "code" => 2000,
                "message" => "Couldn't find the swap.",
            ];
        }

        return [
            "code" => 1000,
            "message" => "Successfully found the swap.",
            "data" => [
                "swap" => $swap
            ]
        ];
    }
}
