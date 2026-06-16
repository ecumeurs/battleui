<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add game_mode to matchmaking_queues — PVE/PVP mode selection per queue entry.
 *
 * @spec-link [[shared:req_matchmaking_matchmaking_queue]]
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('matchmaking_queues', function (Blueprint $table) {
            $table->string('game_mode')->default('1v1_PVP')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matchmaking_queues', function (Blueprint $table) {
            //
        });
    }
};
