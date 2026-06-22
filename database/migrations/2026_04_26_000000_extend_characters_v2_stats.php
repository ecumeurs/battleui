<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extend characters with V2 stat columns missing from ISS-071.
 *
 * @spec-link [[rule_progression]]
 * @spec-link [[rule_stat_taxonomy]]
 *
 * Adds Class A (CP-upgradable) stats absent from prior schema:
 *   - mp           (resource counter, parity with hp; default 10)
 *   - sp           (resource counter, parity with hp; default 10)
 *   - jump_height  (default 2; matches engine PropertiesForCharacter())
 *   - crit_chance  (percent, default 0)
 *   - crit_damage  (percent multiplier, default 0)
 *
 * Class B (AttackRange, Shield) intentionally NOT added — those are
 * granted by items/buffs only, never CP-upgradable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->integer('mp')->default(10)->after('hp');
            $table->integer('sp')->default(10)->after('mp');
            $table->integer('jump_height')->default(2)->after('movement');
            $table->integer('crit_chance')->default(0)->after('jump_height');
            $table->integer('crit_damage')->default(0)->after('crit_chance');
        });

        // Backfill existing rows to engine defaults from PropertiesForCharacter().
        DB::table('characters')->update([
            'mp'          => 10,
            'sp'          => 10,
            'jump_height' => 2,
            'crit_chance' => 0,
            'crit_damage' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn(['mp', 'sp', 'jump_height', 'crit_chance', 'crit_damage']);
        });
    }
};
