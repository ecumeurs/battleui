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
        Schema::create('game_matches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('game_state_cache')->nullable();
            $table->json('grid_cache')->nullable();
            $table->integer('turn')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('concluded_at')->nullable();
            $table->integer('winning_team_id')->nullable();
            $table->enum('game_mode', ['1v1_PVE', '1v1_PVP', '2v2_PVE', '2v2_PVP'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_matches');
    }
};
