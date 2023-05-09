<?php
/*
 * Copyright (c) 2023. Gavin Kasdorf
 * This code is licensed under MIT license (see LICENSE.txt for details)
 */

namespace App\Api\AppleMusic;

use App\Models\User;
use App\Types\ParsedPlaylist;
use App\Types\ParsedSong;
use Exception;
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
     * @return array
     */
    public function getPlaylist(string $id): array
    {
        // Create the url
        $url = "$this->baseUrlMe/library/playlists/$id/tracks";

        $response = json_decode(Http::withHeaders($this->header)->acceptJson()->get($url)->body());
        $tracks = clone($response);

        // Get all the tracks from next
        while (isset($response->next)) {
            $response = json_decode(Http::withHeaders($this->header)->acceptJson()->get($this->rootUrl . $response->next)->body());

            $tracks->data = array_merge($tracks->data, $response->data);

            usleep(500);
        }

        $tracksParsed = [];

        foreach ($tracks->data as $track) {
            try {
                $tracksParsed[] = new ParsedSong(
                    $track->attributes->playParams->id,
                    $track->attributes->name,
                    $track->attributes->artistName,
                    $track->attributes->albumName,
                    $track->attributes->artwork->url ?? null
                );
            } catch (Exception $e) {
                error_log("Error finding song. Moving on.");
                error_log(json_encode($e));
            }
        }

        // Return the response
        return $tracksParsed;
    }

    /**
     * Get user's playlists
     * @return array
     */
    public function getUserPlaylists(): array
    {
        // Create the url
        $url = "$this->baseUrlMe/library/playlists";

        // Return the response
        $resp = json_decode(Http::withHeaders($this->header)->get($url)->body());

        // a foreach loop to check each item in an array to see if it has a description.
        // Get the initial playlists
        $playlists = $resp->data;

        // Set next
        $next = $resp->data->next ?? null;

        while ($next) {
            // Set the url
            $url = "$this->baseUrlMe" . explode("me", $next)[1];

            // Make the request
            $nextResp = json_decode(Http::withHeaders($this->header)->get($url)->body());

            // Merge the playlists
            $playlists = array_merge($playlists, $nextResp->data);
            // Set next
            $next = $nextResp->next ?? null;
        }

        // Parse the playlists
        $parsedPlaylists = [];

        // Loop through each
        foreach ($playlists as $playlist) {
            try {
                $parsedPlaylists[] = new ParsedPlaylist(
                    $playlist->attributes->playParams->id,
                    $playlist->attributes->name,
                    $playlist->attributes->description->standard ?? "",
                    null
                );
            } catch (Exception $e) {
                error_log("Something went wrong with a song. Moving on.");
                error_log(json_encode($e));
            }
        }

        return $parsedPlaylists;
    }

    /**
     * Get a playlist name from the user's library
     * @param $id
     * @return string
     */
    public function getPlaylistName($id): string
    {
        $url = "$this->baseUrlMe/library/playlists/$id";

        return json_decode(Http::withHeaders($this->header)->get($url)->body())->data[0]->attributes->name;
    }

    public function getPlaylistUrl($id): string
    {
        if ($id == "library") {
            return "https://music.apple.com/library";
        }

        return "https://music.apple.com/library/playlist/$id";
    }

    /**
     * Get a user's library
     * @return array
     */
    public function getLibrary(): array
    {
        //TODO While for next
        $data = [
            "limit" => 100,
            "offset" => 0,
            "include" => "library-songs"
        ];

        $url = "$this->baseUrlMe/library/songs";

        $response = json_decode(Http::withHeaders($this->header)->acceptJson()->get($url)->body());

        $parsedSongs = [];

        foreach ($response->data as $song) {
            try {
                $parsedSongs[] = new ParsedSong(
                    $song->attributes->playParams->id,
                    $song->attributes->name,
                    $song->attributes->artistName,
                    $song->attributes->albumName,
                    $song->attributes->artwork->url ?? null
                );
            } catch (Exception $e) {
                error_log("Something went wrong finding a song.");
                error_log(json_encode($e));
            }
        }

        return $parsedSongs;
    }

//    /**
//     * Get a playlist from the catalog
//     * @param string $id
//     * @return object
//     */
//    public function getPlaylist(string $id): object
//    {
//        // Create the url
//        $url = "$this->baseUrl/catalog/$this->storefront/playlists/$id";
//
//        // Return the response
//        return json_decode(Http::withHeaders($this->header)->get($url)->body());
//    }

    /**
     * Get a catalog resource (in this case a track)
     * @param string $q
     * @return ?object
     */
    public function search(string $q): ?object
    {
        // Create the data
        $data = [
            "term" => $q,
            "limit" => 1,
            "types" => "songs"
        ];

        // Create the url - For some reason Apple is not decoding the %2B, so we just pass  along the +
        $url = "$this->baseUrl/catalog/$this->storefront/search?" . str_replace("%2B", "+", http_build_query($data));

        $resp = json_decode(Http::withHeaders($this->header)->get($url)->body());

        try {
            $song = $resp->results->songs ? $resp->results->songs->data[0] : null;

            // Return the response
            return (object)[
                "id" => $song->attributes->playParams->id,
                "name" => $song->attributes->name,
                "artist" => $song->attributes->artistName,
                "album" => $song->attributes->albumName,
                "artwork" => $song->attributes->artwork->url
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Create a new playlist and return the response
     * @param string $name
     * @param array $tracks
     * @param string $description
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
        $createRes = json_decode(Http::withHeaders($this->header)->withBody($jsonData)->post($url)->body());

        return (object)[
            "id" => $createRes->data[0]->id,
            "url" => $this->getPlaylistUrl($createRes->data[0]->id)
        ];
    }
}
