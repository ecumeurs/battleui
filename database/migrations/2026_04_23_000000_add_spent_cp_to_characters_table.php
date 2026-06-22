<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add spent_cp to characters — tracks Character Points consumed via stat upgrades.
 *
 * @spec-link [[entity_character]]
 * @spec-link [[rule_progression]]
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->integer('spent_cp')->default(0)->after('initial_movement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('spent_cp');
        });
    }
};
