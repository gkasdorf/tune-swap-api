<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('playlist_songs', function (Blueprint $table) {
            $table->unsignedBigInteger("playlist_id")->change();
            $table->foreign("playlist_id")->references("id")->on("playlists");

            $table->unsignedBigInteger("song_id")->change();

            $table->foreign("song_id")->references("id")->on("songs");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
