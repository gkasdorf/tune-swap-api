<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Jobs\Swap\SwapHelper;
use App\Models\Playlist;
use App\Models\PlaylistSong;
use App\Models\Song;
use App\Models\Sync;
use App\Models\User;
use App\Types\MusicService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DoSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private User $user;
    private Playlist $fromPlaylist;
    private Playlist $toPlaylist;
    private Sync $sync;

    private array $options;

    private mixed $fromApi;
    private mixed $toApi;

    private array $addToFrom = [];
    private array $addToTo = [];

    public function __construct(Sync $sync, array $options = null)
    {
        $this->user = $sync->user;
        $this->fromPlaylist = $sync->from_playlist;
        $this->toPlaylist = $sync->to_playlist;
        $this->sync = $sync;

        $this->fromApi = Helpers::serviceToApi(MusicService::from($options ? $options["fromService"] : $this->fromPlaylist->service), $this->user);
        $this->toApi = Helpers::serviceToApi(MusicService::from($options ? $options["toService"] : $this->toPlaylist->service), $this->user);
    }

    public function handle(): void
    {
        $this->sync->setRunning(true);

        if ($this->sync->from_playlist_id == null) {
            $this->createPlaylists();
        }
        /**
         * 1. Get the songs from the from playlist
         * 2. Get the songs from the to playlist
         *
         * Check from against to
         *  - If there isn't a track, we will add it.
         *
         * Check the to against the from
         *  - If there isnt a track, we will add it
         */

        $this->comparePlaylists();
        $this->updatePlaylists();

        $this->sync->setSynced();
        $this->sync->setRunning(false);
    }

    private function createPlaylists(): void
    {
        $fromName = $this->fromApi->getPlaylistName($this->options["fromId"]);
        $toName = $this->fromApi->getPlaylistName($this->options["toId"]);

        $this->fromPlaylist = new Playlist([
            "name" => $fromName,
            "user_id" => $this->user->id,
            "service" => $this->options["fromService"],
            "service_id" => $this->options["fromId"]
        ]);
        $this->fromPlaylist->save();

        $this->toPlaylist = new Playlist([
            "name" => $toName,
            "user_id" => $this->user->id,
            "service" => $this->options["toService"],
            "service_id" => $this->options["toId"]
        ]);
        $this->toPlaylist->save();

        SwapHelper::createPlaylist($this->fromPlaylist, $this->fromApi);
        SwapHelper::createPlaylist($this->toPlaylist, $this->toApi);
    }

    private function comparePlaylists(): void
    {
        // Get the currently stored songs
        $fromCurrentSongs = $this->fromPlaylist->playlistSongs()->with("song")->get();
        $toCurrentSongs = $this->toPlaylist->playlistSongs()->with("song")->get();

        // Get the songs that are in the playlist
        $fromLiveSongs = $this->fromApi->getPlaylist($this->fromPlaylist->service_id);
        $toLiveSongs = $this->toApi->getPlaylist($this->toPlaylist->service_id);

        //Get the column names
        $fromColumnName = Helpers::serviceToColumnName($this->fromPlaylist->service);
        $toColumnName = Helpers::serviceToColumnName($this->toPlaylist->service);

        foreach ($fromLiveSongs as $liveSong) {
            if ($toCurrentSongs->firstWhere("song.$fromColumnName", $liveSong->id))
                continue;

            $search = SwapHelper::findTrackId($liveSong, MusicService::from($this->toPlaylist->service), $this->toApi);

            if (!$search)
                continue;

            $this->addToTo[] = $search["trackId"];
            $this->addSongToPlaylist($this->toPlaylist, $liveSong);

            if ($search["usedApi"])
                usleep(500);
        }

        foreach ($toLiveSongs as $liveSong) {
            if ($fromCurrentSongs->firstWhere("song.$toColumnName", $liveSong->id))
                continue;

            $search = SwapHelper::findTrackId($liveSong, MusicService::from($this->fromPlaylist->service), $this->fromApi);

            if (!$search)
                continue;

            $this->addToFrom[] = $search["trackId"];
            $this->addSongToPlaylist($this->fromPlaylist, $liveSong);

            if ($search["usedApi"])
                usleep(500);
        }
    }

    private function addSongToPlaylist(Playlist $playlist, Song $song): void
    {
        $playlistSong = new PlaylistSong([
            "playlist_id" => $playlist->id,
            "song_id" => $song->id
        ]);
        $playlistSong->save();
    }

    private function updatePlaylists(): void
    {
        if (count($this->addToFrom) > 0) {
            $this->fromApi->addTracksToPlaylist($this->fromPlaylist->service_id, $this->addToFrom);
        }

        if (count($this->addToTo) > 0) {
            $this->toApi->addTracksToPlaylist($this->toPlaylist->service_id, $this->addToTo);
        }
    }
}
