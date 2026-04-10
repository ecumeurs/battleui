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
        Schema::table('match_participants', function (Blueprint $table) {
            // Drop naming can vary, but standard Laravel is table_column_foreign
            $table->dropForeign(['player_id']);
            $table->uuid('player_id')->nullable()->change();
            $table->foreign('player_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('match_participants', function (Blueprint $table) {
            $table->dropForeign(['player_id']);
            $table->uuid('player_id')->nullable(false)->change();
            $table->foreign('player_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
