<?php

namespace App\Console;

use App\Jobs\CheckSyncs;
use Exolnet\Heartbeat\HeartbeatFacade;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            if (env("APP_ENV") === "local") {
                error_log("Skipping heartbeat check because we're in local environment.");
                return;
            }

            $cmd = "php " . env("ARTISAN_PATH") . " queue:monitor database:default --max=100";
            $statusCheck = shell_exec($cmd);

            if (str_contains($statusCheck, "OK")) {
                error_log("OK!");
                HeartbeatFacade::channel("http")->signal(env("HEARTBEAT_URL"));
            } else {
                error_log("Nope!");
            }
        })->everyThreeMinutes();

        $schedule->job(new CheckSyncs)->everyMinute()->onOneServer();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
