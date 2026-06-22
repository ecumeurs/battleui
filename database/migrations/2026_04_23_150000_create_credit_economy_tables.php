<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Credit economy — adds credits balance to users and creates credit_transactions ledger.
 *
 * @spec-link [[entity_player_credits]]
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('credits')->default(0)->after('ratio');
        });

        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('player_id');
            $table->bigInteger('amount');
            $table->string('source'); // damage, healing, status
            $table->uuid('match_id')->nullable();
            $table->timestamps();

            $table->foreign('player_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('credits');
        });
    }
};
