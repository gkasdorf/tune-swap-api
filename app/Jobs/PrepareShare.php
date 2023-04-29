<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Http\MusicService;
use App\Models\Playlist;
use App\Models\PlaylistSong;
use App\Models\Share;
use App\Models\Song;
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
        $playlistItems = $this->api->getPlaylist($this->playlist->service_id);
        $data = [];

        foreach ($playlistItems as $item) {
            $song = Song::getById(MusicService::from($this->playlist->service), $this->playlist->service_id);

            if (!$song) {
                $song = new Song([
                    "name" => $item->name,
                    "artist" => $item->artist,
                    "album" => $item->album
                ]);

                switch (MusicService::from($this->playlist->service)) {
                    case MusicService::SPOTIFY:
                    {
                        $song->spotify_id = $item->id;
                        break;
                    }
                    case MusicService::APPLE_MUSIC:
                    {
                        $song->apple_music_id = $item->id;
                        break;
                    }
                    case MusicService::TIDAL:
                    {
                        $song->tidal_id = $item->id;
                        break;
                    }
                }

                $song->save();
            }

            error_log($song->name);

            $data[] = [
                "playlist_id" => $this->playlist->id,
                "song_id" => $song->id
            ];
        }

        PlaylistSong::insert($data);

        $this->share->ready = true;
        $this->share->save();
    }
}
