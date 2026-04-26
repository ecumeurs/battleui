<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Set users.credits default to 1000 and backfill zero-balance accounts.
 *
 * @spec-link [[shared:rule_starting_credits_1000]]
 *
 * V2 design decision: every new user starts with 1000 credits.
 * Backfill is idempotent (only updates rows currently at 0).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('credits')->default(1000)->change();
        });

        DB::table('users')->where('credits', 0)->update(['credits' => 1000]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('credits')->default(0)->change();
        });
    }
};
