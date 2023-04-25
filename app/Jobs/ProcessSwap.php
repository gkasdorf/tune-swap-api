<?php

namespace App\Jobs;

use App\Api\AppleMusic\AppleMusic;
use App\Api\Spotify\Spotify;
use App\Api\Tidal\Tidal;
use App\Http\MusicService;
use App\Models\Swap;
use App\Models\SwapStatus;
use App\Models\User;
use App\Notifications\SwapComplete;
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
    private string $playlistName;


    public function __construct(User $user, Swap $swap)
    {
        $this->user = $user;
        $this->swap = $swap;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Set the status to finding music
        $this->swap->setStatus(SwapStatus::FINDING_MUSIC);
        $this->swap->save();

        // Create a new normalize instance
        $normalize = new NormalizePlaylist(
            $this->swap,
            $this->user
        );

        // Normalize the playlist
        $normalized = $normalize->normalize();

        // Update the status
        $this->swap->setStatus(SwapStatus::BUILDING_PLAYLIST);
        $this->swap->save();

        $api = null;

        switch (MusicService::from($this->swap->to_service)) {
            case MusicService::SPOTIFY:
                $api = new Spotify($this->user);
                break;
            case MusicService::APPLE_MUSIC:
                $api = new AppleMusic($this->user);
                break;
            case MusicService::TIDAL:
                $api = new Tidal($this->user);
                break;
            case MusicService::PANDORA:
                throw new \Exception('To be implemented');
        }

        $api->createPlaylist($this->swap->playlist_name, $normalized["ids"], $this->swap->description);

        // Update the status
        $this->swap->setStatus(SwapStatus::COMPLETED);
        $this->swap->save();

        if ($this->user->iosNotificationsEnabled()) {
            $this->user->notify(new SwapComplete($this->swap));
        }
    }

    public function failed(\Exception $exception)
    {
        $this->swap->setStatus(SwapStatus::ERROR);

        error_log(json_encode($exception));
    }
}
