<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Http\MusicService;
use App\Jobs\Swap\SwapHelper;
use App\Models\Playlist;
use App\Models\Share;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PrepareShare implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private mixed $api;
    private Share $share;
    private Playlist $playlist;
    private User $user;

    /**
     * Create a new job instance.
     */
    public function __construct(Share $share)
    {
        $this->share = $share;
        $this->playlist = $share->playlist;
        $this->user = $share->user;

        $this->api = Helpers::serviceToApi(MusicService::from($this->playlist->service), $this->user);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        SwapHelper::createPlaylist($this->playlist, $this->api);

        $this->share->ready = true;
        $this->share->save();
    }
}
