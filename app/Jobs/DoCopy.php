<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Jobs\Swap\SwapHelper;
use App\Models\Copy;
use App\Models\Playlist;
use App\Models\Share;
use App\Models\SwapStatus;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DoCopy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Copy $copy;
    private User $user;
    private Share $share;
    private Playlist $playlist;
    private mixed $api;

    private array $trackIds = [];

    /**
     * Create a new job instance.
     */
    public function __construct(Copy $copy, User $user)
    {
        $this->copy = $copy;
        $this->user = $user;

        $this->share = $this->copy->share;
        $this->playlist = $this->share->playlist;

        $this->api = Helpers::serviceToApi($this->copy->service, $this->user);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $playlistSongs = $this->playlist->playlistSongs()->get();

        $total = count($playlistSongs);
        $current = 0;

        foreach ($playlistSongs as $playlistSong) {
            $song = $playlistSong->song;

            try {
                $search = SwapHelper::findTrackId($song, $this->copy->service, $this->api);

                if ($search) {
                    $this->trackIds[] = $search["trackId"];

                    if ($search["usedApi"]) usleep(500);
                }
            } catch (\Exception $e) {
                error_log("There was an error!");
                error_log($e->getMessage());
                error_log($e->getLine());
                error_log($e->getFile());
            }

            $current++;
            $this->copy->progress = round(($current / $total) * 100);

            if ($current % 15 == 0) {
                $this->copy->save();
            }
        }

        $this->copy->status = SwapStatus::BUILDING_PLAYLIST;
        $this->copy->save();

        $create = $this->api->createPlaylist(
            $this->playlist->name,
            $this->trackIds,
            "Shared with TuneSwap"
        );

        $this->copy->status = SwapStatus::COMPLETED;
        $this->copy->save();
    }
}
