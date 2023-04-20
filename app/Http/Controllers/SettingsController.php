<?php

namespace App\Http\Controllers;

use App\Models\Settings;

class SettingsController extends Controller
{
    public function getMaintenance(): array
    {
        $settings = Settings::getSettings();

        return [
            "code" => 1000,
            "message" => "Got maintenance settings",
            "data" => [
                "maintenance" => $settings->maintenance,
                "message" => $settings->maintenance_message
            ]
        ];
    }
}
