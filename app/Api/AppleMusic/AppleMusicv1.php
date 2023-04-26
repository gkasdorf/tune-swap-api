<?php
/*
 * Copyright (c) 2023. Gavin Kasdorf
 * This code is licensed under MIT license (see LICENSE.txt for details)
 */

namespace App\Api\AppleMusic;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class AppleMusicv1
{
    // TODO We should be setting the proper storefront based on the user
    private string $storefront = "us";
    private array $header;

    // Apple music base url for non-user queries
    private string $baseUrl = "https://api.music.apple.com/v1";
    // Apple music base url for user queries
    private string $baseUrlMe = "https://api.music.apple.com/v1/me";

    private string $rootUrl = "https://api.music.apple.com";

    public function __construct(User $user, $token = null)
    {
        // Create our header
        $this->header = [
            "Authorization" => "Bearer " . env("APPLE_MUSIC_TOKEN")
        ];

        // If the user already has a token, use that
        if (!empty($user->apple_music_token)) {
            $this->header["Music-User-Token"] = "$user->apple_music_token";
        } // Else we will use the token passed
        else if (!empty($token)) {
            $this->header["Music-User-Token"] = $token;
        }
    }

    /**
     * Get a user's storefront localization
     * @return object
     */
    public function getUserStorefront(): object
    {
        // Create the url
        $url = "$this->baseUrlMe/storefront";

        // Return the response
        return json_decode(Http::withHeaders($this->header)->get($url)->body());
    }

    /**
     * Get a user's playlist
     * @param string $id
     * @return object
     */
    public function getUserPlaylist(string $id): object
    {
        // Create the url
        $url = "$this->baseUrlMe/library/playlists/$id/tracks";

        $response = json_decode(Http::withHeaders($this->header)->acceptJson()->get($url)->body());
        $tracks = clone($response);

        while (isset($response->next)) {
            $response = json_decode(Http::withHeaders($this->header)->acceptJson()->get($this->rootUrl . $response->next)->body());

            $tracks->data = array_merge($tracks->data, $response->data);
        }

        // REturn the response
        return $tracks;
    }

    /**
     * Get user's playlists
     * @return object
     */
    public function getUserPlaylists(): object
    {
        // Create the url
        $url = "$this->baseUrlMe/library/playlists";

        // Return the response
        $resp = json_decode(Http::withHeaders($this->header)->get($url)->body(), true);

        // a foreach loop to check each item in an array to see if it has a description.
        // Get the initial playlists
        $playlists = $resp["data"];

        // Set next
        $next = $resp["next"] ?? null;

        // Get all from next
        while ($next) {
            // Set the url
            $url = "$this->baseUrlMe" . explode("me", $next)[1];

            // Make the request
            $nextResp = json_decode(Http::withHeaders($this->header)->get($url)->body(), true);

            // Merge the playlists
            $playlists = array_merge($playlists, $nextResp["data"]);
            // Set next
            $next = $nextResp["next"] ?? null;
        }

        // Loop through all playlists
        // TODO Remove this once we release new iOS version
        for ($i = 0; $i < count($playlists); $i++) {
            if (!isset($playlists[$i]["attributes"]["description"])) {
                $playlists[$i]["attributes"]["description"] = [
                    "standard" => "No description provided."
                ];
            }
        }

        // Set the playlists
        $resp["data"] = $playlists;

        return (object)$resp;
    }

    /**
     * Get a playlist name from the user's library
     * @return string
     */
    public function getUserPlaylistName($id)
    {
        $url = "$this->baseUrlMe/library/playlists/$id";

        return json_decode(Http::withHeaders($this->header)->get($url)->body())->data[0]->attributes->name;
    }

    public function getLibrary()
    {
        $data = [
            "limit" => 100,
            "offset" => 0,
            "include" => "library-songs"
        ];

        $url = "$this->baseUrlMe/library/songs";

        $response = json_decode(Http::withHeaders($this->header)->acceptJson()->get($url)->body());

        return $response;
    }

    /**
     * Get a playlist from the catalog
     * @param string $id
     * @return object
     */
    public function getPlaylist(string $id): object
    {
        // Create the url
        $url = "$this->baseUrl/catalog/$this->storefront/playlists/$id";

        // Return the response
        return json_decode(Http::withHeaders($this->header)->get($url)->body());
    }

    /**
     * Get a catalog resource (in this case a track)
     * @param array $query Array containing the necessary params for search
     *      $query = [
     *          'name' => (string) The track to search for
     *          'artist' => (string) The artist to search for
     *          'album' => (string) The album to search for
     *      ]
     * @return object
     */
    public function search(array $query, $retry = false): object
    {
        // Since we are really only worried about finding tracks right now, we will just default to that.

        // Make our search term
        if ($retry) {
            $lower = strtolower($query["name"]);

            // Remove "recorded at"
            if (str_contains($lower, "recorded at")) {
                $lower = explode("recorded at", $lower)[0];
            }

            // Remove "xxxx remaster"
            if (str_contains($lower, "20")) {
                $lower = explode("20", $lower)[0];
                $lower = str_replace("(", "", $lower);
            }

            $query["name"] = $lower;

            $term = $query["name"] . " " . $query["artist"];
        } else {
            $term = $query["name"] . " " . $query["artist"] . " " . $query["album"];
        }

        // Fix our term
        $term = str_replace(" ", "+", $term);
        $term = str_replace("'", "", $term);
        $term = str_replace("-", "", $term);
        $term = str_replace("++", "+", $term);

        // Create the data
        $data = [
            "term" => $term,
            "limit" => 1,
            "types" => "songs"
        ];

        // Create the url - For some reason Apple is not decoding the %2B, so we just pass  along the +
        $url = "$this->baseUrl/catalog/$this->storefront/search?" . str_replace("%2B", "+", http_build_query($data));

        $resp = Http::withHeaders($this->header)->get($url);

        // Return the response
        return json_decode($resp->body());
    }

    /**
     * Create a new playlist and return the response
     * @param string $name
     * @param array $tracks
     * @return object
     */
    public function createPlaylist(string $name, array $tracks, string $description = ""): object
    {
        // Create our data. This is gonna be json (fucking apple lol)
        $data = [
            "attributes" => [
                "name" => $name,
                "description" => $description
            ],
            "relationships" => [
                "tracks" => [
                    "data" => [
                    ]
                ]
            ]
        ];

        // Add each track to the data
        foreach ($tracks as $track) {
            $data["relationships"]["tracks"]["data"][] = [
                "id" => $track,
                "type" => "songs"
            ];
        }

        // JSON encode the data
        $jsonData = json_encode($data);

        // Set the URL for the post
        $url = $this->baseUrlMe . "/library/playlists";

        // Submit the post request with the json and return the result
        return json_decode(Http::withHeaders($this->header)->withBody($jsonData)->post($url)->body());
    }
}
