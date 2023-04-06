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
        Schema::create('swaps', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("status")->default("Created");
            $table->integer("user_id")->index();
            $table->string("from_service");
            $table->string("to_service");
            $table->string("playlist_name");
            $table->string("playlist_id")->nullable();
            $table->string("from_playlist_id");
            $table->string("from_playlist_url", 500)->nullable();
            $table->integer("total_songs")->default(0);
            $table->integer("songs_found")->default(0);
            $table->integer("songs_not_found")->default(0);
            $table->json("songs_not_found_list")->nullable();
            $table->boolean("will_sync")->default(false);
            $table->string("to_service_id")->nullable();
            $table->string("to_service_url", 500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('swaps');
    }
};
