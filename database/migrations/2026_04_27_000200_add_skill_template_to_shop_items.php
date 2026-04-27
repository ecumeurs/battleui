<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allow exotic items to carry a skill via nullable FK (ISS-073 D11).
 *
 * @spec-link [[mec_item_carried_skill]]
 *
 * Schema decisions (per ISS-073/086 prep plan §2 Decisions):
 *   D11: vanilla weapons use stat properties only; exotic items (throwables, traps,
 *        grenade-launcher-style weapons) additionally point at a skill_template.
 *        At battle-init the bridge appends the linked skill to the entity's registered
 *        skills alongside the item's stat buffs — the two paths coexist.
 *        SET NULL on delete ensures item catalog remains intact if a template is removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->uuid('skill_template_id')->nullable()->after('available');
            $table->foreign('skill_template_id')->references('id')->on('skill_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->dropForeign(['skill_template_id']);
            $table->dropColumn('skill_template_id');
        });
    }
};
