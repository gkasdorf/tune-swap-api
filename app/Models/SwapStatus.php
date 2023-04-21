<?php

namespace App\Models;

enum SwapStatus: string
{
    case CREATED = "Created";
    case QUEUED = "Queued";
    case FINDING_MUSIC = "Finding Music";
    case BUILDING_PLAYLIST = "Building Playlist";
    case CLEANING_UP = "Cleaning Up";
    case COMPLETED = "Completed";
    case CANCELLED = "Cancelled";
    case ERROR = "Error";
}
