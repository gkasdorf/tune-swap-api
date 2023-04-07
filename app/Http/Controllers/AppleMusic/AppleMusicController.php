<?php

namespace App\Http\Controllers\AppleMusic;

use App\AppleMusic\AppleMusic;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AppleMusicController extends Controller
{
    public function playlist(Request $request): JsonResponse
    {
        $appleMusic = new AppleMusic($request->user());


        return response()->json($appleMusic->getPlaylist("p.AW2AcLYzoJDk"));
    }

    public function userPlaylist(Request $request): JsonResponse
    {
        $appleMusic = new AppleMusic($request->user());

        return response()->json($appleMusic->getUserPlaylist($request->id));
    }

    public function userPlaylists(Request $request): JsonResponse
    {
        $appleMusic = new AppleMusic($request->user());

        return response()->json($appleMusic->getUserPlaylists());
    }

    public function userPlaylistName(Request $request, $id): Response
    {
        $appleMusic = new AppleMusic($request->user());

        return response($appleMusic->getUserPlaylistName($id));
    }

    public function storefront(Request $request): JsonResponse
    {
        $appleMusic = new AppleMusic($request->user());

        return response()->json($appleMusic->getUserStorefront());
    }
}
