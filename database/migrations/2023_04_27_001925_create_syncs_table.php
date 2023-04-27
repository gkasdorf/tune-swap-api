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
        Schema::create('syncs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId("user_id")->constrained("users")->onDelete("cascade");
            $table->foreignId("from_playlist_id")->constrained("playlists")->onDelete("cascade");
            $table->foreignId("to_playlist_id")->constrained("playlists")->onDelete("cascade");
            $table->timestamp("last_synced")->nullable();
            $table->timestamp("last_checked")->nullable();
            $table->timestamp("last_updated");
            $table->boolean("syncing")->default(true);
            $table->integer("custom_time")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('syncs');
    }
};
