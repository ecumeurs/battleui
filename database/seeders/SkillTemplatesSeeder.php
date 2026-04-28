<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed the V2.0 skill template catalog.
 *
 * @spec-link [[entity_skill_template]]
 *
 * Three core skills with deterministic UUIDs for test stability.
 */
class SkillTemplatesSeeder extends Seeder
{
    public const FIREBALL_ID = '00000000-0000-4000-9000-000000000001';
    public const HEAL_ID     = '00000000-0000-4000-9000-000000000002';
    public const SPRINT_ID   = '00000000-0000-4000-9000-000000000003';

    public function run(): void
    {
        $now = now();

        $templates = [
            [
                'id'              => self::FIREBALL_ID,
                'name'            => 'Fireball',
                'behavior'        => 'Direct',
                'targeting'       => json_encode(['Type' => 'Single', 'Range' => 3]),
                'costs'           => json_encode(['MP' => 3]),
                'effect'          => json_encode(['Type' => 'Damage', 'Value' => 10]),
                'grade'           => 'I',
                'weight_positive' => 5,
                'weight_negative' => 0,
                'available'       => true,
                'version'         => '2.0',
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'id'              => self::HEAL_ID,
                'name'            => 'Heal',
                'behavior'        => 'Direct',
                'targeting'       => json_encode(['Type' => 'Single', 'Range' => 2]),
                'costs'           => json_encode(['MP' => 4]),
                'effect'          => json_encode(['Type' => 'Recovery', 'Value' => 10]),
                'grade'           => 'I',
                'weight_positive' => 5,
                'weight_negative' => 0,
                'available'       => true,
                'version'         => '2.0',
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'id'              => self::SPRINT_ID,
                'name'            => 'Sprint',
                'behavior'        => 'Direct',
                'targeting'       => json_encode(['Type' => 'Self', 'Range' => 0]),
                'costs'           => json_encode(['SP' => 2]),
                'effect'          => json_encode(['Type' => 'Buff', 'Stat' => 'Movement', 'Value' => 2, 'Duration' => 2]),
                'grade'           => 'I',
                'weight_positive' => 3,
                'weight_negative' => 0,
                'available'       => true,
                'version'         => '2.0',
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
        ];

        DB::table('skill_templates')->upsert($templates, ['id'], [
            'name', 'behavior', 'targeting', 'costs', 'effect', 'grade', 'weight_positive', 'weight_negative', 'available', 'version', 'updated_at',
        ]);
    }
}
