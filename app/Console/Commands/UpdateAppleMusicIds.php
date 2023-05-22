<?php

namespace App\Console\Commands;

use App\Api\AppleMusic\AppleMusic;
use App\Jobs\Swap\SwapHelper;
use App\Models\Song;
use App\Models\User;
use App\Types\MusicService;
use Illuminate\Console\Command;

class UpdateAppleMusicIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-apple-music-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix any incorrect Apple Music IDs in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::firstWhere("id", 1);

        $api = new AppleMusic($user);

        $songs = Song::where("apple_music_id", "like", "%i.%")->get();

        error_log("Found " . count($songs) . " songs with incorrect Apple Music IDs");
        error_log("Starting update in 10 seconds...");
        
        sleep(10);

        $count = 0;

        foreach ($songs as $song) {
            $song->apple_music_id = null;
            $search = SwapHelper::findTrackId($song, MusicService::APPLE_MUSIC, $api);

            if (!$search) {
                error_log("Could not find Apple Music ID for {$song->name} by {$song->artist}");
                continue;
            }

            error_log("Found Apple Music ID for {$song->name} by {$song->artist}: {$search["trackId"]}");
            usleep(500);

            $count++;

            if ($count == 100) {
                error_log("Sleeping for 1 minute...");
                sleep(60);
                $count = 0;
            }
        }
    }
}
