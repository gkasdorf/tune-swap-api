<?php

namespace App\Http\Controllers\v1\Spotify;

use App\Api\Spotify\Spotifyv1;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SpotifyController extends Controller
{
    public function myPlaylists(Request $request)
    {
        $spotify = new Spotifyv1($request->user());

        return response()->json($spotify->getUserPlaylists());
    }

    public function playlist(Request $request, $id)
    {
        $spotify = new Spotifyv1($request->user());

        return response()->json($spotify->getPlaylist($id));
    }

    public function library(Request $request)
    {
        $spotify = new Spotifyv1($request->user());

        return response()->json($spotify->getLibrary());
    }
}
