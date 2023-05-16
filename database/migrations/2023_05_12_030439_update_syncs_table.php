<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('syncs', function (Blueprint $table) {
            $table->dropForeign("syncs_from_playlist_id_foreign");
            $table->dropForeign("syncs_to_playlist_id_foreign");
        });

        Schema::table('syncs', function (Blueprint $table) {
            $table->dropColumn("from_playlist_id");
            $table->dropColumn("to_playlist_id");
        });

        Schema::table('syncs', function (Blueprint $table) {
            $table->foreignId("from_playlist_id")->nullable()->constrained("playlists")->onDelete("cascade");
            $table->foreignId("to_playlist_id")->nullable()->constrained("playlists")->onDelete("cascade");
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
