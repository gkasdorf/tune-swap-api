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

    private static string $authUrl = "https://login.tidal.com/authorize";

    public function __construct(User $user, $token = null)
    {
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
     * Courtesy Jack https://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
     * @return string|void
     */
    private static function genUUIDv4()
    {
        $data = openssl_random_pseudo_bytes(16);

        assert(strlen($data) == 16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function createAuthUrl(): array
    {
        // Generate a challenge code and verifier
        $verifier_bytes = random_bytes(64);
        $codeVerifier = rtrim(strtr(base64_encode($verifier_bytes), "+/", "-_"), "=");
        $challengeBytes = hash("sha256", $codeVerifier, true);
        $codeChallenge = rtrim(strtr(base64_encode($challengeBytes), "+/", "-_"), "=");

        $uniqueKey = self::genUUIDv4();

        $params = [
            "appMode" => "WEB",
            // this isn't sensitive as it isn't our ID. Should it be in the .env? Yea, but we will put it here so that
            // anyone who ever runs across this and needs it can have it :)
            "client_id" => "CzET4vdadNUFQ5JU",
            "client_unique_key" => $uniqueKey,
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
            "uniqueKey" => $uniqueKey
        ];
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
