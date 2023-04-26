<?php

namespace App\Models;

class ParsedSong
{
    public string $name;
    public string $artist;
    public string $album;
    public string $image;
    public string $id;

    public function __construct(string $id, string $name, string $artist, string $album, ?string $image)
    {
        $this->name = $name;
        $this->artist = $artist;
        $this->album = $album;
        $this->image = $image;
        $this->id = $id;
    }
}
