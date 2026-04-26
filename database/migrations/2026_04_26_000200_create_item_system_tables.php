<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create the four ISS-074 item-system tables.
 *
 * @spec-link [[upsilonbattle:entity_shop_item]]
 * @spec-link [[upsilonbattle:entity_player_inventory]]
 * @spec-link [[upsilonbattle:entity_character_equipment]]
 * @spec-link [[upsilonbattle:mec_credit_spending_shop]]
 *
 * Schema decisions (per ISS-074 prep plan §2 Decisions):
 *   D1: player_inventory has NO character_id; equip lives only in character_equipment.
 *   D7: slot enums stored as varchar+CHECK, not Postgres ENUM.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Catalog of purchasable items.
        Schema::create('shop_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('type', 32);
            $table->string('slot', 16);
            $table->json('properties');
            $table->integer('cost');
            $table->boolean('available')->default(true);
            $table->string('version', 10)->default('2.0');
            $table->timestamps();
        });

        // CHECK constraint for slot enum.
        DB::statement("ALTER TABLE shop_items ADD CONSTRAINT shop_items_slot_check CHECK (slot IN ('armor','utility','weapon'))");

        // Per-user owned items (ownership only — no equip state here).
        Schema::create('player_inventory', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('player_id');
            $table->uuid('shop_item_id');
            $table->integer('quantity')->default(1);
            $table->timestamp('purchased_at')->useCurrent();
            $table->timestamps();

            $table->foreign('player_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('shop_item_id')->references('id')->on('shop_items');
            $table->unique(['player_id', 'shop_item_id']);
            $table->index('player_id');
        });

        // Audit trail for purchases / refunds.
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('player_id');
            $table->uuid('shop_item_id');
            $table->integer('quantity');
            $table->integer('credits_spent');
            $table->string('transaction_type', 16)->default('purchase');
            $table->timestamps();

            $table->foreign('player_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('shop_item_id')->references('id')->on('shop_items');
            $table->index('player_id');
        });

        DB::statement("ALTER TABLE inventory_transactions ADD CONSTRAINT inventory_transactions_type_check CHECK (transaction_type IN ('purchase','refund','gift','admin_grant'))");

        // 3-slot equipment binding (one row per character, lazily created).
        Schema::create('character_equipment', function (Blueprint $table) {
            $table->uuid('character_id')->primary();
            $table->uuid('armor_item_id')->nullable();
            $table->uuid('utility_item_id')->nullable();
            $table->uuid('weapon_item_id')->nullable();
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
            $table->foreign('armor_item_id')->references('id')->on('player_inventory')->nullOnDelete();
            $table->foreign('utility_item_id')->references('id')->on('player_inventory')->nullOnDelete();
            $table->foreign('weapon_item_id')->references('id')->on('player_inventory')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_equipment');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('player_inventory');
        Schema::dropIfExists('shop_items');
    }
};
