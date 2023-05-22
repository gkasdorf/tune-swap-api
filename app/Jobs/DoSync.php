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
    private ?Playlist $fromPlaylist;
    private ?Playlist $toPlaylist;
    private Sync $sync;

    private ?array $options;

    private mixed $fromApi;
    private mixed $toApi;

    private array $addToFrom = [];
    private array $addToTo = [];

    public function __construct(Sync $sync, array $options = null)
    {
        $this->user = $sync->user;
        $this->fromPlaylist = $sync->fromPlaylist;
        $this->toPlaylist = $sync->toPlaylist;
        $this->sync = $sync;
        $this->options = $options;

        $this->fromApi = Helpers::serviceToApi(MusicService::from($options ? $options["fromService"] : $this->fromPlaylist->service), $this->user);
        $this->toApi = Helpers::serviceToApi(MusicService::from($options ? $options["toService"] : $this->toPlaylist->service), $this->user);
    }

    public function handle(): void
    {
        $this->sync->setRunning(true);

        if ($this->sync->from_playlist_id == null) {
            $this->createPlaylists();
        }

        $this->comparePlaylists();
        $this->updatePlaylists();
        $this->updateTotals();

        $this->sync->setSynced();
        $this->sync->setChecked();
        $this->sync->setRunning(false);
    }

    private function createPlaylists(): void
    {
        error_log("Creating playlists...");

        $fromName = $this->fromApi->getPlaylistName($this->options["fromId"]);
        $toName = $this->toApi->getPlaylistName($this->options["toId"]);

        $this->fromPlaylist = new Playlist([
            "name" => $fromName,
            "user_id" => $this->user->id,
            "service" => $this->options["fromService"],
            "service_id" => $this->options["fromId"]
        ]);
        $this->fromPlaylist->save();

        error_log("First playlist created...");

        $this->toPlaylist = new Playlist([
            "name" => $toName,
            "user_id" => $this->user->id,
            "service" => $this->options["toService"],
            "service_id" => $this->options["toId"]
        ]);
        $this->toPlaylist->save();

        $this->sync->from_playlist_id = $this->fromPlaylist->id;
        $this->sync->to_playlist_id = $this->toPlaylist->id;
        $this->sync->save();

        error_log("Second playlist created...");

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

        foreach ($fromLiveSongs as $parsedSong) {
            // See if the song is in the from playlist
            $song = $fromCurrentSongs->firstWhere("song.$fromColumnName", $parsedSong->id)?->song;

            // If not
            if (!$song) {
                // See if we have the song in the database
                $song = Song::firstWhere($fromColumnName, $parsedSong->id);

                // If not create it
                if (!$song) {
                    error_log("Not in DB. Creating.");
                    $song = SwapHelper::createSong($parsedSong, $this->fromPlaylist->service);
                }

                // Add it to the from playlist
                $this->addSongToPlaylist($this->fromPlaylist, $song);
            }

            // See if we have the song in the to playlist and continue if we do
            if ($toCurrentSongs->firstWhere("song.$fromColumnName", $parsedSong->id))
                continue;

            // If not...
            // See if we already have the to playlist song id
            if (!$song[$toColumnName]) {
                // If we don't we will try to find it
                $search = SwapHelper::findTrackId($song, MusicService::from($this->toPlaylist->service), $this->toApi);

                // If we can't find the id, we will continue.
                if (!$search)
                    continue;

                // Add the id to the song
                $song[$toColumnName] = $search["trackId"];
                $song->save();

                if ($search["usedApi"]) {
                    usleep(500);
                }
            }

            // Add the song to the to playlist and add it to the tracks to add to the playlist
            $this->addSongToPlaylist($this->toPlaylist, $song);
            $this->addToTo[] = $song[$toColumnName];
        }

        foreach ($toLiveSongs as $parsedSong) {
            // See if the song is in the to playlist
            $song = $toCurrentSongs->firstWhere("song.$toColumnName", $parsedSong->id)?->song;

            // If not
            if (!$song) {
                // See if we have the song in the database
                $song = Song::firstWhere($toColumnName, $parsedSong->id);

                // If not create it
                if (!$song) {
                    $song = SwapHelper::createSong($parsedSong, $this->toPlaylist->service);
                }

                // Add it to the from playlist
                $this->addSongToPlaylist($this->toPlaylist, $song);
            }

            // See if we have the song in the to playlist and continue if we do
            if ($fromCurrentSongs->firstWhere("song.$toColumnName", $parsedSong->id))
                continue;

            // If not...
            // See if we already have the to playlist song id
            if (!$song[$fromColumnName]) {
                // If we don't we will try to find it
                $search = SwapHelper::findTrackId($song, MusicService::from($this->fromPlaylist->service), $this->fromApi);

                // If we can't find the id, we will continue.
                if (!$search)
                    continue;

                // Add the id to the song
                $song[$fromColumnName] = $search["trackId"];
                $song->save();

                if ($search["usedApi"]) {
                    usleep(500);
                }
            }

            // Add the song to the to playlist and add it to the tracks to add to the playlist
            $this->addSongToPlaylist($this->fromPlaylist, $song);
            $this->addToFrom[] = $song[$fromColumnName];
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
            error_log("Updating playlist...");
            $this->fromApi->addTracksToPlaylist($this->fromPlaylist->service_id, $this->addToFrom);
        }

        if (count($this->addToTo) > 0) {
            error_log("Updating playlist...");
            $this->toApi->addTracksToPlaylist($this->toPlaylist->service_id, $this->addToTo);
        }
    }

    private function updateTotals(): void
    {
        $this->sync->from_total = $this->fromApi->getPlaylistTotal($this->fromPlaylist->service_id);
        $this->sync->to_total = $this->toApi->getPlaylistTotal($this->toPlaylist->service_id);
        $this->sync->save();
    }
}
