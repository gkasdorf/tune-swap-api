<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Http\MusicService;
use App\Jobs\Swap\SwapHelper;
use App\Models\Playlist;
use App\Models\SongNotFound;
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

    private array $trackIds = [];


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
        $this->user->setIsRunning(true);
        $this->swap->setStatus(SwapStatus::FINDING_MUSIC);

        // Create the playlist
        $playlist = new Playlist([
            "name" => $this->swap->playlist_name,
            "user_id" => $this->user->id,
            "service" => $this->swap->from_service,
            "service_id" => $this->swap->from_playlist_id
        ]);
        $playlist->save();

        // Get tracks from the playlist and save them to the playlist
        $songs = SwapHelper::createPlaylist($playlist, $this->fromApi);

        $this->swap->total_songs = count($songs);
        $this->swap->save();

        // Attempt to find each song
        foreach ($songs as $song) {
            try {
                $search = SwapHelper::findTrackId($song, MusicService::from($this->swap->to_service), $this->toApi);

                if (!$search) {
                    $this->swap->songs_not_found++;
                    $notFound = new SongNotFound([
                        "song_id" => $song->id,
                        "swap_id" => $this->swap->id
                    ]);

                    $this->swap->save();
                    $notFound->save();

                    continue;
                }

                $this->swap->songs_found++;

                // Let's save some memory and not save every time we update
                if ($this->swap->songs_found % 15 == 0) {
                    $this->swap->save();
                }

                $this->trackIds[] = $search["trackId"];

                if ($search["usedApi"]) usleep(500);
            } catch (\Exception $e) {
                error_log("There was an error!");
                error_log($e->getMessage());
                error_log($e->getLine());
                error_log($e->getFile());
            }
        }

        $this->swap->setStatus(SwapStatus::BUILDING_PLAYLIST);

        $create = $this->toApi->createPlaylist(
            $this->swap->playlist_name,
            $this->trackIds,
            $this->swap->description ?? ""
        );

        $this->swap->setFromData($this->fromApi->getPlaylistUrl($this->swap->from_playlist_id));
        $this->swap->setToData($create);

        $this->swap->setStatus(SwapStatus::COMPLETED);
        $this->user->setIsRunning(false);

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
        $this->user->setIsRunning(false);

        error_log(json_encode($exception));
    }
}
