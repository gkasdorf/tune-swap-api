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
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("name");
            $table->string("artist");
            $table->string("album");
            $table->string("genre")->nullable();
            $table->string("storefront")->nullable();
            $table->string("spotify_id")->nullable()->index();
            $table->string("apple_music_id")->nullable()->index();
            $table->string("tidal_id")->nullable()->index();
            $table->string("pandora_id")->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};
