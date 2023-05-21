<?php
/*
 * Copyright (c) 2023. Gavin Kasdorf
 * This code is licensed under MIT license (see LICENSE.txt for details)
 */

namespace App\Types;


enum MusicService: string
{
    case SPOTIFY = "Spotify";
    case APPLE_MUSIC = "Apple Music";
    case TIDAL = "Tidal";
    case PANDORA = "Pandora";
}
