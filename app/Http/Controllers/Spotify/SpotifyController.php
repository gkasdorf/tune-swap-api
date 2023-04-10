<?php

namespace App\Http\Controllers\Spotify;

use App\Http\Controllers\Controller;
use App\Spotify\Spotify;
use Illuminate\Http\Request;

class SpotifyController extends Controller
{
    public function myPlaylists(Request $request)
    {
        $spotify = new Spotify($request->user());

        return response()->json($spotify->getUserPlaylists());
    }

    public function playlist(Request $request, $id)
    {
        $spotify = new Spotify($request->user());

        return response()->json($spotify->getPlaylist($id));
    }

    public function library(Request $request)
    {
        $spotify = new Spotify($request->user());

        return response()->json($spotify->getLibrary());
    }
}
