<?php

namespace App\Api\Tidal;

use App\Models\ParsedPlaylist;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class Tidal
{
    private array $header = [];
    private string $countryCode = "US";
    private string $locale = "en_US";

    private string $baseUrlv1 = "https://api.tidal.com/v1";
    private string $baseUrlv2 = "https://api.tidal.com/v2";

    private static string $authUrl = "https://login.tidal.com/authorize";

    private static string $tokenUrl = "https://login.tidal.com/oauth2/token";

    private static string $clientId = "CzET4vdadNUFQ5JU";

    private User $user;

    /**
     * Pass in the user and the token (default null, will get it from DB if not supplied)
     * @param User $user
     * @param $token
     */
    public function __construct(User $user, $token = null)
    {
        $this->user = $user;

        if ($this->user->tidal_expiration <= time() - 600) {
            $this->user->tidal_token = self::refresh($user);
        }

        if (!empty($user->tidal_token)) {
            $this->header["Authorization"] = "Bearer " . $user->tidal_token;
        } else if (!empty($token)) {
            $this->header["Authorization"] = "Bearer " . $token;
        }
    }

    /**
     * Tidal is annoying and does not provide a public API, so we will be doing a bit of hacking here and intercepting
     * the auth redirect using the TS app. Unfortunately there won't be a way to do Tidal auths in the browser at this
     * time due to CORS limitations. They SAID they are going to have a public API release in the near future but also
     * declined to give any comment on WHEN "near future" is. So until then, we are stuck with this.
     *
     * We want to always generate the URL on the server side incase Tidal does change their client ID. Don't want to
     * have Tidal auth broken until an approval by Apple.
     *
     * First we direct the user to the URL we are generating here, going to https://login.tidal.com/authorize with the
     * params we generate here.
     *
     * Next, we will listen for the redirect to https://listen.tidal.com/login/auth. We will get the OAuth code here.
     *
     *
     */

    /**
     * Returns the current Tidal auth url
     * @return array
     * @throws \Exception
     */
    public static function createAuthUrl(): array
    {
        // Generate a challenge code and verifier
        $verifier_bytes = random_bytes(64);
        $codeVerifier = rtrim(strtr(base64_encode($verifier_bytes), "+/", "-_"), "=");
        $challengeBytes = hash("sha256", $codeVerifier, true);
        $codeChallenge = rtrim(strtr(base64_encode($challengeBytes), "+/", "-_"), "=");

        $params = [
            "appMode" => "WEB",
            // this isn't sensitive as it isn't our ID. Should it be in the .env? Yea, but we will put it here so that
            // anyone who ever runs across this and needs it can have it :)
            "client_id" => self::$clientId,
            "code_challenge" => $codeChallenge,
            "code_challenge_method" => "S256",
            "lang" => "en",
            "redirect_uri" => "https://listen.tidal.com/login/auth", // Obviously your redirect_uri can be whatever you
            // want it to be as long as it is whitelisted
            // as valid by Tidal. Most of their URLs though
            // deeplink to either the Android, Desktop, or
            // ios apps, so going to the web player is the
            // easiest to process to avoid any annoying
            // "invalid" errors on the app.
            "response_type" => "code",
            "restrictSignup" => "true",
            "scope" => "r_usr w_usr",
            "autoredirect" => "true"
        ];

        $tidalUrl = self::$authUrl . "?" . http_build_query($params);

        return [
            "url" => $tidalUrl,
            "codeVerifier" => $codeVerifier,
            "codeChallenge" => $codeChallenge,
        ];
    }

    /**
     * Performs authentication with Tidal
     * @param string $code
     * @param string $codeVerifier
     * @return mixed
     */
    public static function auth(string $code, string $codeVerifier): mixed
    {
        // Create our data
        $data = [
            "client_id" => self::$clientId,
            "code" => $code,
            "code_verifier" => $codeVerifier,
            "grant_type" => "authorization_code",
            "redirect_uri" => "https://listen.tidal.com/login/auth",
            "scope" => "r_usr w_usr"
        ];

        // Return the auth response
        return json_decode(Http::acceptJson()->post(self::$tokenUrl, $data)->body());
    }

    /**
     * Refreshes the Tidal token and returns the new token
     * @param User $user
     * @return string|null
     */
    public static function refresh(User $user): ?string
    {
        $url = self::$tokenUrl;

        $data = [
            "client_id" => self::$clientId,
            "refresh_token" => $user->tidal_refresh_token,
            "grant_type" => "refresh_token",
            "scope" => "r_usr w_usr"
        ];

        $response = Http::acceptJson()->asForm()->post($url, $data);

        if ($response->clientError()) {
            return null;
        }

        $result = json_decode($response->body());

        error_log(json_encode($result));

        $user->tidal_token = $result->access_token;
        $user->tidal_expiration = $result->expires_in + time();

        $user->save();

        return $user->tidal_token;
    }

    /**
     * Return all the user's playlists
     * @return array
     */
    public function getUserPlaylists(): array
    {
        //TODO While for next
        // Create our data
        $data = [
            "folderId" => "root",
            "includeOnly" => "",
            "offset" => 0,
            "limit" => 50,
            "order" => "DATE",
            "orderDirection" => "DESC"
        ];
        // Create our URL
        $url = "$this->baseUrlv2/my-collection/playlists/folders?" . http_build_query($data);

        $resp = json_decode(Http::withHeaders($this->header)->acceptJson()->get($url)->body());

        $parsedPlaylists = [];

        foreach ($resp->items as $playlist) {
            $parsedPlaylists[] = new ParsedPlaylist(
                $playlist->data->uuid,
                $playlist->data->title,
                $playlist->data->description ?? "No description provided.",
                null
            );
        }

        return $parsedPlaylists;
    }

    /**
     * Get a Tidal playlist by ID
     * @param string $id
     * @return array
     */
    public function getPlaylist(string $id): array
    {
        // Create our data
        $data = self::addCountryCode([
            "limit" => 100,
            "offset" => 0
        ]);

        // Create our url
        $url = "$this->baseUrlv1/playlists/$id/tracks?" . http_build_query($data);

        // Get the response
        $response = json_decode(Http::withHeaders($this->header)->acceptJson()->get($url)->body());

        // SEt our offset to 0
        $offset = 0;

        // Set the total number of items in the playlist
        $totalItems = $response->totalNumberOfItems;

        // Create the initial array with the first items
        $tracks = $response->items;

        // Get the rest of the items
        while ($offset + 100 < $totalItems) {
            // Add 100 to the offset and set our data
            $offset = $offset + 100;
            $data["offset"] = $offset;

            // Create the new URL
            $url = "$this->baseUrlv1/playlists/$id/tracks?" . http_build_query($data);

            // Get the response
            $response = json_decode(Http::withHeaders($this->header)->acceptJson()->get($url)->body());

            // Add the tracks to the array
            $tracks = array_merge($tracks, $response->items);
        }

        return $tracks;
    }

    /**
     * Get the name of a playlist
     * @param string $id
     * @return string
     */
    public function getPlaylistName(string $id): string
    {
        // Create the data
        $data = self::addCountryCode([]);

        // Create our URL
        $url = "$this->baseUrlv1/playlists/$id?" . http_build_query($data);

        // Return the name
        return json_decode(Http::withHeaders($this->header)->acceptJson()->get($url)->body())->title;
    }

    /**
     * Get the tracks from the library. Same as for a plylist, but the limit is 10000 (!)
     * @return array
     */
    public function getLibrary(): array
    {
        $data = self::addCountryCode([
            "limit" => 10000,
            "offset" => 0,
            "order" => "DATE",
            "orderDirection" => "DESC"
        ]);

        $url = "$this->baseUrlv1/users/" . $this->user->tidal_user_id . "/favorites/tracks?";
        $urlWithData = $url . http_build_query($data);

        $response = json_decode(Http::withHeaders($this->header)->acceptJson()->get($urlWithData)->body());

        // Set our offset
        $offset = 0;

        // Set our total items
        $totalItems = $response->totalNumberOfItems;

        // Create our array
        $tracks = $response->items;

        while ($offset + 10000 < $totalItems) {
            // Add 100 to the offset and set our data
            $offset = $offset + 100;
            $data["offset"] = $offset;

            // Create the new URL
            $urlWithData = $url . http_build_query($data);

            // Get the response
            $response = json_decode(Http::withHeaders($this->header)->acceptJson()->get($urlWithData)->body());

            // Add the tracks to the array
            $tracks = array_merge($tracks, $response->items);
        }

        return $tracks;
    }

    /**
     * @param array $query
     *      $query = [
     *          'name' => (string) The track to search for
     *          'artist' => (string) The artist to search for
     *          'album' => (string) The album to search for
     * @return object|null
     */
    public function search(array $query): ?object
    {
        $query["name"] = explode("(", $query["name"])[0];
        // Create the term
        $term = str_replace("'", "", $query["name"]) . " " . str_replace("'", "", $query["artist"]);

        // Create our data
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

        // Create our URL
        $url = "$this->baseUrlv1/search?" . http_build_query($data);

        // Get that response
        $resp = Http::withHeaders($this->header)->get($url);

        try {
            // Try to return the song
            return json_decode($resp->body())->tracks->items[0];
        } catch (\Exception) {
            // If there wasn't anything we just return null
            error_log("Didn't find song.");
            error_log(json_encode($query));
            return null;
        }
    }

    /**
     * Create a playlist. Will return the data for the newly created playlist.
     * @param string $name
     * @param array $tracks
     * @return mixed
     */
    public function createPlaylist(string $name, array $tracks, string $description = ""): mixed
    {

        // Set the create data
        $data = self::addCountryCode([
            "name" => $name,
            "description" => $description,
            "isPublic" => false,
            "folderId" => "root"
        ]);

        // Set the URL for create
        $url = "$this->baseUrlv2/my-collection/playlists/folders/create-playlist?" . http_build_query($data);

        // Create the playlist
        $createResp = json_decode(Http::withHeaders($this->header)->put($url)->body());

        // Get the playlist ID
        $playlistId = $createResp->data->uuid;

        // Cut up that shit...chunky chunky
        $chunks = array_chunk($tracks, 50);

        // Set the add items url
        $url = "$this->baseUrlv1/playlists/$playlistId/items";

        $this->header["If-None-Match"] = "*";

        // For each chunk...
        foreach ($chunks as $chunk) {
            // Create a string from the chunk
            $chunkStr = implode(",", $chunk);

            // Create the data array
            $data = [
                "trackIds" => $chunkStr,
                "onArtifactNotFound" => "FAIL",
                "onDupes" => "FAIL"
            ];

            // Post that shit
            Http::withHeaders($this->header)->asForm()->post($url, $data);
        }

        // Return the data about the playlist
        return $createResp;
    }

    /**
     * Add countryCode and locale to data
     * @param $data
     * @return array
     */
    public static function addCountryCode($data): array
    {
        $data["countryCode"] = "US";
        $data["locale"] = "en_US";

        return $data;
    }
}
