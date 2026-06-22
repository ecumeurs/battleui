<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Initial matchmaking_queues table — queue entry per user with character selection.
 *
 * @spec-link [[shared:req_matchmaking_matchmaking_queue]]
 * @spec-link [[rule_matchmaking_single_queue]]
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('matchmaking_queues', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->index();
            $table->json('character_ids');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matchmaking_queues');
    }
};
