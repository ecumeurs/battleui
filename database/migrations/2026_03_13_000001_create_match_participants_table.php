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
        Schema::create('match_participants', function (Blueprint $table) {
            $table->id();
            $table->uuid('match_id')->index();
            $table->uuid('player_id')->index();
            $table->integer('team');
            $table->enum('status', ['WIN', 'LOSS'])->nullable();
            $table->timestamps();

            $table->foreign('match_id')->references('id')->on('game_matches')->onDelete('cascade');
            $table->foreign('player_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_participants');
    }
};
