<?php

namespace App\Jobs;

use App\AppleMusic\AppleMusic;
use App\Http\MusicService;
use App\Models\Swap;
use App\Models\SwapStatus;
use App\Models\User;
use App\Notifications\SwapComplete;
use App\Spotify\Spotify;
use App\Tidal\Tidal;
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
            MusicService::from($this->swap->from_service),
            MusicService::from($this->swap->to_service),
            $this->swap,
            $this->user
        );

        // Normalize the playlist
        $normalized = $normalize->normalize();

        // Update the status
        $this->swap->setStatus(SwapStatus::BUILDING_PLAYLIST);
        $this->swap->save();

        // Figure out where we are sending the playlist
        if (MusicService::from($this->swap->to_service) == MusicService::SPOTIFY) {
            $api = new Spotify($this->user);
            $api->createPlaylist($this->swap->playlist_name, $normalized["ids"]);
        } else if (MusicService::from($this->swap->to_service) == MusicService::APPLE_MUSIC) {
            $api = new AppleMusic($this->user);
            $api->createPlaylist($this->swap->playlist_name, $normalized["ids"]);
        } else if (MusicService::from($this->swap->to_service) == MusicService::TIDAL) {
            $api = new Tidal($this->user);
            $api->createPlaylist($this->swap->playlist_name, $normalized["ids"]);
        }

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
