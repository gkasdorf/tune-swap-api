<?php
/*
 * Copyright (c) 2023. Gavin Kasdorf
 * This code is licensed under MIT license (see LICENSE.txt for details)
 */

namespace App\AppleMusic;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class AppleMusic
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
            error_log("we are here");
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
        $data = clone($response);

        while (isset($response->next)) {
            $response = json_decode(Http::withHeaders($this->header)->acceptJson()->get($this->rootUrl . $response->next)->body());

            $data->data = array_merge($data->data, $response->data);
        }

        // REturn the response
        return $data;
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
        return json_decode(Http::withHeaders($this->header)->get($url)->body());
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
    public function search(array $query): object
    {
        // Since we are really only worried about finding tracks right now, we will just default to that.

        // Make our search term
        $term = str_replace("'", "", $query["name"]) . "+" . str_replace("'", "", $query["artist"]) . "+" . str_replace("'", "", $query["album"]);
        $term = str_replace(" ", "+", $term);

        error_log("Our term is " . $term);

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
    public function createPlaylist(string $name, array $tracks): object
    {
        // Create our data. This is gonna be json (fucking apple lol)
        $data = [
            "attributes" => [
                "name" => $name,
                "description" => "Transferred with TuneSwap"
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
