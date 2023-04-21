<?php

namespace App\Http\Controllers\v1\AppleMusic;

use App\Api\AppleMusic\AppleMusicv1;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AppleMusicController extends Controller
{
    public function playlist(Request $request): JsonResponse
    {
        $appleMusic = new AppleMusicv1($request->user());


        return response()->json($appleMusic->getPlaylist("p.AW2AcLYzoJDk"));
    }

    public function library(Request $request): JsonResponse
    {
        $appleMusic = new AppleMusicv1($request->user());

        return response()->json($appleMusic->getLibrary());
    }

    public function userPlaylist(Request $request): JsonResponse
    {
        $appleMusic = new AppleMusicv1($request->user());

        return response()->json($appleMusic->getUserPlaylist($request->id));
    }

    public function userPlaylists(Request $request): JsonResponse
    {
        $appleMusic = new AppleMusicv1($request->user());

        return response()->json($appleMusic->getUserPlaylists());
    }

    public function userPlaylistName(Request $request, $id): Response
    {
        $appleMusic = new AppleMusicv1($request->user());

        return response($appleMusic->getUserPlaylistName($id));
    }

    public function storefront(Request $request): JsonResponse
    {
        $appleMusic = new AppleMusicv1($request->user());

        return response()->json($appleMusic->getUserStorefront());
    }
}
