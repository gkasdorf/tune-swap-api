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
        Schema::table('swaps', function (Blueprint $table) {
            $table->dropForeign(["user_id"]);
            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
        });

        Schema::table('playlists', function (Blueprint $table) {
            $table->dropForeign(["user_id"]);
            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
        });

        Schema::table('playlist_songs', function (Blueprint $table) {
            $table->dropForeign(["playlist_id"]);
            $table->foreign("playlist_id")->references("id")->on("playlists")->onDelete("cascade");
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
