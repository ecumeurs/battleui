<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-managed skill design library (ISS-086).
 *
 * @spec-link [[entity_skill_template]]
 * @spec-link [[rule_admin_content_authority]]
 *
 * Schema decisions (per ISS-073/086 prep plan §2 Decisions):
 *   D2: skill registry is Laravel-master; full JSON payload carried at battle-init.
 *   D5: character_skills stores a snapshot at acquisition time; templates are the design source.
 *   D7: behavior/grade stored as varchar+CHECK (not Postgres ENUM).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('behavior', 16);
            $table->json('targeting');
            $table->json('costs');
            $table->json('effect');
            $table->string('grade', 8);
            $table->integer('weight_positive')->default(0);
            $table->integer('weight_negative')->default(0);
            $table->boolean('available')->default(true);
            $table->string('version', 10)->default('2.0');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE skill_templates ADD CONSTRAINT skill_templates_behavior_check CHECK (behavior IN ('Direct','Reaction','Passive','Counter','Trap'))");
        DB::statement("ALTER TABLE skill_templates ADD CONSTRAINT skill_templates_grade_check CHECK (grade IN ('I','II','III','IV','V'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_templates');
    }
};
