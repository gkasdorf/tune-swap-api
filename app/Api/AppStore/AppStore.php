<?php

namespace App\Api\AppStore;

use Illuminate\Support\Facades\Http;

class AppStore
{
    private static string $prodUrl = "https://buy.itunes.apple.com/verifyReceipt";
    private static string $sandboxUrl = "https://sandbox.itunes.apple.com/verifyReceipt";

    private string $url;
    private string $secret;

    public function __construct()
    {
        $this->url = env("APP_DEBUG") ? self::$sandboxUrl : self::$prodUrl;
        $this->secret = env("APP_STORE_SECRET");
    }

    public function verifyReceipt(string $receipt)
    {
        $data = [
            "password" => $this->secret,
            "receipt-data" => $receipt,
            "exclude-old-transactions" => false
        ];

        return json_decode(Http::acceptJson()->post($this->url, $data)->body());
    }
}
