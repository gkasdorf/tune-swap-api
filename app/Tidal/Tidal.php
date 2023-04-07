<?php

namespace App\Tidal;

use Illuminate\Support\Facades\Http;

class Tidal
{
    private array $header;
    private string $countryCode = "US";
    private string $locale = "en_US";

    private string $baseUrlv1 = "https://api.tidal.com/v1";
    private string $baseUrlv2 = "https://api.tidal.com/v2";

    public function __construct(User $user, $token = null)
    {
        if (!empty($user->tidal_token)) {
            $this->header["Authorization"] = "Bearer " . $user->tidal_token;
        } else if (!empty($token)) {
            $this->header["Authorization"] = "Bearer " . $token;
        }
    }

    public function getUserPlaylist(string $id): object
    {
        $url = "$this->baseUrlv1/playlists/$id/tracks";

        return json_decode(Http::withHeaders($this->header)->acceptJson()->get($url)->body());
    }

    public function getUserPlaylists(): object
    {
        $url = "";

        return json_decode(Http::withHeaders($this->header)->accpetJson()->get($url)->body());
    }

    public function getUserPlaylistName(string $id): object
    {
        $url = "$this->baseUrlv1/playlist/$id";

        return json_decode(Http::withHeaders($this->header)->acceptJson()->get($url)->body());
    }

    public function search(array $query): object
    {
        $term = str_replace("'", "", $query["name"]) . " " . str_replace("'", "", $query["artist"]) . " " . str_replace("'", "", $query["album"]);

        $data = [
            "query" => $term,
            "limit" => 1,
            "types" => "TRACKS",
            "includeContributors" => false,
            "includUserPlaylists" => false,
            "supportsUserData" => false,
            "countryCode" => "US",
            "locale" => "en_US"
        ];

        $url = "$this->baseUrlv1/search?" . http_build_query($data);

        $resp = Http::withHeaders($this->header)->get($url);

        return json_decode($resp->body());
    }

    public function createPlaylist(string $name, array $tracks): object
    {
        $createData = [
            "name" => $name,
            "description" => "Transferred with TuneSwap",
            "isPublic" => false,
            "folderId" => "root",
            "countryCode" => "US",
            "locale" => "en_US"
        ];

        $url = "$this->baseUrlv2/my-collection/playlists/folders/create-playlist?" . http_build_query($createData);

        $createResp = json_decode(Http::withHeaders($this->header)->put($url)->body());

        $playlistId = $createResp->id;

        $tracksStr = implode(",", $tracks);

        $tracksData = [
            "trackIds" => $tracksStr
        ];

        $url = "$this->baseUrlv1/playlists/$playlistId/items";

        Http::withHeaders($this->header)->post($url, $tracksData);

        return $createResp;
    }
}
