<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add initial_movement to characters — snapshot of base Move stat before in-game consumption.
 *
 * @spec-link [[entity_character]]
 * @spec-link [[rule_stat_taxonomy]]
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->integer('initial_movement')->nullable()->after('movement');
        });

        // Seed initial_movement from current movement for existing rows
        DB::table('characters')->update([
            'initial_movement' => DB::raw('movement')
        ]);

        Schema::table('characters', function (Blueprint $table) {
            $table->integer('initial_movement')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('initial_movement');
        });
    }
};
