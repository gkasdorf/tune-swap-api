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
        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("access_id")->unique()->index();
            $table->foreignId("user_id")->constrained("users")->onDelete("cascade");
            $table->foreignId("playlist_id")->constrained("playlists")->onDelete("cascade");
            $table->integer("saves")->default(0);
            $table->boolean("ready")->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
