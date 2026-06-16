<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrade characters.player_id FK to CASCADE DELETE — characters are owned by their player.
 *
 * @spec-link [[entity_character]]
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropForeign(['player_id']);
            $table->foreign('player_id')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropForeign(['player_id']);
            $table->foreign('player_id')
                  ->references('id')
                  ->on('users');
        });
    }
};
