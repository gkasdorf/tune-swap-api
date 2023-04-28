<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Http\MusicService;
use App\Models\Swap;
use App\Models\SwapStatus;
use App\Models\User;
use App\Notifications\SwapComplete;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSwap implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private User $user;
    private Swap $swap;

    private mixed $fromApi;
    private mixed $toApi;
    private string $playlistName;


    public function __construct(User $user, Swap $swap)
    {
        $this->user = $user;
        $this->swap = $swap;

        $this->setApis();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Set the status to finding music
        $this->swap->setStatus(SwapStatus::FINDING_MUSIC);

        // Create a new normalize instance
        $normalize = new NormalizePlaylist(
            $this->swap,
            $this->user,
            $this->fromApi,
            $this->toApi
        );

        // Normalize the playlist
        $normalized = $normalize->normalize();

        // Update the status
        $this->swap->setStatus(SwapStatus::BUILDING_PLAYLIST);

        // Create the playlist
        $createRes = $this->toApi->createPlaylist($this->swap->playlist_name, $normalized["ids"], $this->swap->description);

        // Set the from playlist URL
        $this->swap->setFromData($this->fromApi->getPlaylistUrl($this->swap->from_playlist_id));
        $this->swap->setToData($createRes);

        // Update the status
        $this->swap->setStatus(SwapStatus::COMPLETED);

        // Send a notification if enabled
        if ($this->user->iosNotificationsEnabled()) {
            $this->user->notify(new SwapComplete($this->swap));
        }
    }

    /**
     * @throws Exception
     */
    private function setApis()
    {
        $this->fromApi = Helpers::serviceToApi(MusicService::from($this->swap->from_service), $this->user);
        $this->toApi = Helpers::serviceToApi(MusicService::from($this->swap->to_service), $this->user);
    }

    public function failed(Exception $exception)
    {
        $this->swap->setStatus(SwapStatus::ERROR);

        error_log(json_encode($exception));
    }
}
