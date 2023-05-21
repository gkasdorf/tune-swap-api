<?php

namespace App\Api\GooglePlay;

use Google\Client;
use Google\Service\AndroidPublisher;

class GooglePlay
{
    private static string $subscriptionsGetUrl = "https://androidpublisher.googleapis.com/androidpublisher/v3/applications/{packageName}/purchases/subscriptions/{subscriptionId}/tokens/{token}";
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->addScope(AndroidPublisher::ANDROIDPUBLISHER);

        $credentials = (array)json_decode(file_get_contents(env("GOOGLE_APPLICATION_CREDENTIALS")));
        $this->client->setAuthConfig($credentials);
    }

    public function verifyReceipt(string $packageName, string $subscriptionId, string $token): object|bool
    {
        $url = str_replace("{packageName}", $packageName, self::$subscriptionsGetUrl);
        $url = str_replace("{subscriptionId}", $subscriptionId, $url);
        $url = str_replace("{token}", $token, $url);

        $httpClient = $this->client->authorize();

        try {
            return json_decode($httpClient->get($url)->getBody());
        } catch (\Exception) {
            return false;
        }
    }
}
