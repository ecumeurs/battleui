<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-character skill inventory with snapshot model (ISS-073).
 *
 * @spec-link [[entity_character_skill_inventory]]
 * @spec-link [[rule_character_skill_slots]]
 *
 * Schema decisions (per ISS-073/086 prep plan §2 Decisions):
 *   D5: instance_data is a JSON snapshot at acquisition time; skill_template_id is informational only.
 *   D6: source enum — 'roll' wired in V2.0; others reserved.
 *   D8: equipped boolean on this table; slot enforcement in SkillService.
 *   D11: source='item' is reserved but never INSERTed in V2.0 (item-derived skills are virtualized).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_skills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('character_id');
            $table->uuid('skill_template_id')->nullable();
            $table->string('source', 16);
            $table->json('instance_data');
            $table->boolean('equipped')->default(false);
            $table->timestamp('acquired_at')->useCurrent();
            $table->timestamp('equipped_at')->nullable();
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
            $table->foreign('skill_template_id')->references('id')->on('skill_templates')->nullOnDelete();

            $table->index(['character_id', 'equipped']);
        });

        DB::statement("ALTER TABLE character_skills ADD CONSTRAINT character_skills_source_check CHECK (source IN ('roll','template','shop','grant','item'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('character_skills');
    }
};
