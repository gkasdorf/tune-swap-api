<?php
/*
 * Copyright (c) 2023. Gavin Kasdorf
 * This code is licensed under MIT license (see LICENSE.txt for details)
 */

namespace App\Api\Spotify;

use App\Models\User;
use App\Types\ParsedPlaylist;
use App\Types\ParsedSong;
use Exception;
use Illuminate\Support\Facades\Http;

class Spotify
{
    private string $baseUrl;
    private array $header;

    private User $user;

    /**
     * @param User $user The TS user that is being used
     */
    public function __construct(User $user)
    {
        // Check if the access token has expired
        if ($user->spotify_expiration <= time() - 900) {
            $user->spotify_token = SpotifyAuthentication::refresh($user);
        }
        $this->baseUrl = "https://api.spotify.com/v1";
        $this->header = [
            "Authorization" => "Bearer " . $user->spotify_token,
            "Content-Type" => "application/json"
        ];

        $this->user = $user;
    }

    private function checkToken(): void
    {
        error_log("Checking token.");

        if ($this->user->spotify_expiration <= time() - 600) {
            $this->user->spotify_token = SpotifyAuthentication::refresh($this->user);
        }
    }

    /**
     * Returns the playlist for the user
     * @return array $profile
     *      $profile = [
     *          'email' => (string)
     *          'id' => (string)
     *      ]
     */
    public function getProfile(): array
    {
        $url = $this->baseUrl . "/me";

        $response = json_decode(Http::withHeaders($this->header)->acceptJson()->asForm()->get($url)->body());

        return [
            "email" => $response->email,
            "id" => $response->id
        ];
    }

    /**
     * Return the user's playlists
     * @return array
     */
    public function getUserPlaylists(): array
    {
        //TODO While for next
        $data = [
            "limit" => 50
        ];

        $url = $this->baseUrl . "/me/playlists?" . http_build_query($data);

        $resp = json_decode(Http::withHeaders($this->header)->acceptJson()->asForm()->get($url)->body());

        $parsedPlaylists = [];

        try {
            foreach ($resp->items as $playlist) {
                $parsedPlaylists[] = new ParsedPlaylist(
                    $playlist->id,
                    $playlist->name,
                    $playlist->description ?? "No description provided.",
                    $playlist->images[0]->url ?? null
                );
            }
        } catch (Exception $e) {
            error_log(json_encode($e));
        }

        return $parsedPlaylists;
    }

    /**
     *
     * @param string $id The playlist's ID
     * @return array All the tracks in the playlist
     */
    public function getPlaylist(string $id): array
    {
        $data = [
            "limit" => 50
        ];

        $url = $this->baseUrl . "/playlists/" . $id . "/tracks?" . http_build_query($data);

        $response = json_decode(Http::withHeaders($this->header)->acceptJson()->get($url)->body());

        $tracks = $response->items;

        while ($response->next) {
            $response = json_decode(Http::withHeaders($this->header)->acceptJson()->get($response->next));

            $tracks = array_merge($tracks, $response->items);

            usleep(500);
        }

        $parsedTracks = [];


        foreach ($tracks as $track) {
            try {
                if (!$track->track->id) continue;

                $parsedTracks[] = new ParsedSong(
                    $track->track->id,
                    $track->track->name,
                    $track->track->artists[0]->name,
                    $track->track->album->name,
                    $track->track->album->images[0]->url ?? null
                );
            } catch (Exception $e) {
                error_log("Error getting song. Moving on.");
                error_log(json_encode($e));
            }
        }

        return $parsedTracks;
    }

    /**
     * @param string $id The playlist's id
     * @return string The name of the playlist
     */
    public function getPlaylistName(string $id): string
    {
        $url = $this->baseUrl . "/playlists/" . $id;

        $response = json_decode(Http::withHeaders($this->header)->acceptJson()->get($url)->body());

        return $response->name;
    }

    /**
     * Returns the playlist URL if available
     * @param $id
     * @return string|null
     */
    public function getPlaylistUrl($id): ?string
    {
        if ($id == "library") {
            return "https://open.spotify.com/collection/tracks";
        }

        $url = $this->baseUrl . "/playlists/" . $id;

        $response = json_decode(Http::withHeaders($this->header)->acceptJson()->get($url)->body());

        return $response->external_urls->spotify ?? null;
    }

    /**
     * @return array All the tracks in the library
     */
    public function getLibrary(): array
    {
        $data = [
            "limit" => 50
        ];

        $url = $this->baseUrl . "/me/tracks?" . http_build_query($data);

        $response = json_decode(Http::withHeaders($this->header)->acceptJson()->get($url)->body());

        $tracks = $response->items;

        while ($response->next) {
            $response = json_decode(Http::withHeaders($this->header)->acceptJson()->get($response->next));

            $tracks = array_merge($tracks, $response->items);
        }

        $parsedTracks = [];

        foreach ($tracks as $track) {
            try {
                $parsedTracks[] = new ParsedSong(
                    $track->track->id,
                    $track->track->name,
                    $track->track->artists[0]->name,
                    $track->track->album->name,
                    $track->track->album->images[0]->url ?? null
                );
            } catch (Exception $e) {
                error_log("Something went wrong finding a song. Moving on.");
                error_log(json_encode($e));
            }
        }

        return $parsedTracks;
    }

    /**
     * @param string $q
     * @return object|null Returns the search results if there were any, null if none
     */
    public function search(string $q): ?object
    {
        $this->checkToken();

        $data = [
            "q" => $q,
            "type" => "track",
            "limit" => 1,
        ];

        $url = "$this->baseUrl/search?" . http_build_query($data);

        $resp = json_decode(Http::withHeaders($this->header)->get($url)->body());
        $track = $resp->tracks->items[0];

        if (!$track) {
            return null;
        }

        return (object)[

            "id" => $track->id,
            "name" => $track->name,
            "artist" => $track->artists[0]->name,
            "album" => $track->album->name,
            "image" => $track->album->images[0]->url
        ];
    }

    /**
     * @param string $name Name of the playlist
     * @param array $tracks Array of all the track IDs to add
     * @return object The result of the createPlaylist call
     */
    public function createPlaylist(string $name, array $tracks, ?string $description = ""): object
    {
        $this->checkToken();

        // Create our data
        $data = [
            "name" => $name,
            "description" => $description,
            "public" => false
        ];

        // encode the data
        $jsonData = json_encode($data);

        // Set the url
        $url = "$this->baseUrl/users/" . $this->user->spotify_user_id . "/playlists";

        // Send the request
        $createResponse = json_decode(Http::withHeaders($this->header)->withBody($jsonData)->post($url));

        // Create chunks of 100 elements. Spotify API only allows adding 100 songs per request
        $chunks = array_chunk($tracks, 100);

        // Create the url for posting the songs to
        $url = "$this->baseUrl/playlists/$createResponse->id/tracks";

        // For each chunk...
        foreach ($chunks as $chunk) {
            // For each track in the chunk...
            foreach ($chunk as $key => $track) {
                // Prepend the necessary data for spotify
                $chunk[$key] = "spotify:track:$track";
            }

            // Create our data to send
            $data = [
                "uris" => $chunk
            ];

            // encode baby encode
            $jsonData = json_encode($data);

            // make the request
            Http::withHeaders($this->header)->withBody($jsonData)->post($url);
        }

        return (object)[
            "id" => $createResponse->id,
            "url" => $createResponse->external_urls->spotify ?? null
        ];
    }
}
