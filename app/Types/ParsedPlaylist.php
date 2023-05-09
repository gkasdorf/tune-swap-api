<?php

namespace App\Types;

class ParsedPlaylist
{
    public string $id;
    public string $name;
    public string $description;
    public ?string $image;

    public function __construct(string $id, string $name, string $description, ?string $image)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->image = $image;
    }
}
