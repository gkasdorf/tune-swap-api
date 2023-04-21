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
        Schema::create('songs_not_found', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId("swap_id")->constrained("swaps")->onDelete("cascade");
            $table->foreignId("song_id")->constrained("songs")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('songs_not_found');
    }
};
