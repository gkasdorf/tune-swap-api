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
        Schema::table('users', function (Blueprint $table) {
            $table->string("name");
            $table->string("spotify_token", 500)->nullable();
            $table->string("spotify_expiration")->nullable();
            $table->string("spotify_refresh_token")->nullable();
            $table->string("spotify_email")->nullable();
            $table->string("spotify_user_id")->nullable();
            $table->string("apple_music_token", 500)->nullable();
            $table->string("apple_music_storefront")->nullable();
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
