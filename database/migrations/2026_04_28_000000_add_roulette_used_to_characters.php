<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-character "skill roulette consumed" flag (ISS-073 frontend follow-up).
 *
 * Mirrors the reroll-on-spawn pattern: each character starts with one roulette
 * available; the flag flips on first successful skill roll and never resets.
 * Exposed via CharacterResource as `roulette_available = !roulette_used`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->boolean('roulette_used')->default(false)->after('spent_cp');
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('roulette_used');
        });
    }
};
