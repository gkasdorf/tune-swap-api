<?php

namespace App\Http\Controllers\Tidal;

use App\Http\Controllers\Controller;
use App\Tidal\Tidal;
use Illuminate\Http\Request;

class TidalController extends Controller
{
    public function authUrl(Request $request)
    {
        return [
            "code" => 1000,
            "message" => "User is authorized, URL created",
            "data" => Tidal::createAuthUrl()
        ];
    }
}
