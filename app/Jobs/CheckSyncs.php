<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Models\Sync;
use App\Types\SubscriptionType;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckSyncs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /**
         * 1. Get all syncs that are currently active
         * 2. Check that the sync is not currently running
         * 3. Check that the sync needs to be updated
         * 4. Dispatch the job if the sync needs to be performed, if not, update the last checked time.
         */

        $checkTime = env("APP_DEBUG") ? 0 : 5;

        $turboSyncs = Sync::getActive()
            ->where("last_checked", "<=", Carbon::now()->subMinutes($checkTime))
            ->whereHas("user.subscriptions", function ($q) {
                $q->where("subscription_type", SubscriptionType::TURBO)
                    ->where("end_date", ">=", Carbon::now()->toDateTimeString());
            })->get();

        error_log(json_encode($turboSyncs));

        $otherSyncs = Sync::getActive()
            ->where("last_checked", "<=", Carbon::now()->subMinutes(60))
            ->whereDoesntHave("user.subscriptions", function ($q) {
                $q->where("subscription_type", SubscriptionType::TURBO)
                    ->where("end_date", ">=", Carbon::now());
            })->get();

        error_log(json_encode($otherSyncs));

        foreach ($turboSyncs as $sync) {
            $this->checkPlaylists($sync);
        }

        foreach ($otherSyncs as $sync) {
            $this->checkPlaylists($sync);
        }
    }

    private function checkPlaylists(Sync $sync): void
    {
        error_log("Checking #" . $sync->id);

        $fromApi = Helpers::serviceToApi($sync->fromPlaylist->service, $sync->user);
        $toApi = Helpers::serviceToApi($sync->toPlaylist->service, $sync->user);

        try {
            $fromTotal = $fromApi->getPlaylistTotal($sync->fromPlaylist->service_id);
            $toTotal = $toApi->getPlaylistTotal($sync->toPlaylist->service_id);
        } catch (\Exception) {
            //TODO - Handle this better
            $sync->setSyncing();
            return;
        }

        $sync->setChecked();

        if ($fromTotal !== $sync->from_total || $toTotal !== $sync->to_total) {
            $sync->setRunning(true);
            DoSync::dispatch($sync);
        }
    }
}
