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
        Schema::table('playlists', function (Blueprint $table) {
            $table->dropColumn("has_spotify");
            $table->dropColumn("has_apple_music");
            $table->dropColumn("has_tidal");
            $table->renameColumn("original_service", "service");
            $table->renameColumn("original_id", "service_id");
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
