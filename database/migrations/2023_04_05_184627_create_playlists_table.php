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
        Schema::create('playlists', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer("user_id")->index();
            $table->string("name");
            $table->boolean("has_spotify")->default(false);
            $table->boolean("has_apple_music")->default(false);
            $table->boolean("has_tidal")->default(false);
            $table->boolean("has_pandora")->default(false);
            $table->string("genre")->nullable();
            $table->integer("swaps")->default(0);
            $table->string("original_service")->nullable();
            $table->string("original_id")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playlists');
    }
};
