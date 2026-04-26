<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed the V2.0 shop catalog.
 *
 * @spec-link [[upsilonbattle:entity_shop_item]]
 * @spec-link [[upsilonbattle:rule_item_pricing_simple]]
 *
 * Three items, deterministic UUIDs (test stability), fixed costs per V2.0.
 */
class ShopItemsSeeder extends Seeder
{
    public const BASIC_ARMOR_ID  = '00000000-0000-4000-8000-000000000001';
    public const BASIC_SWORD_ID  = '00000000-0000-4000-8000-000000000002';
    public const SWIFT_BOOTS_ID  = '00000000-0000-4000-8000-000000000003';

    public function run(): void
    {
        $now = now();

        $items = [
            [
                'id'         => self::BASIC_ARMOR_ID,
                'name'       => 'Basic Armor',
                'type'       => 'armor',
                'slot'       => 'armor',
                'properties' => json_encode(['ArmorRating' => 5]),
                'cost'       => 200,
                'available'  => true,
                'version'    => '2.0',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => self::BASIC_SWORD_ID,
                'name'       => 'Basic Sword',
                'type'       => 'weapon',
                'slot'       => 'weapon',
                'properties' => json_encode([
                    'WeaponBaseDamage' => 5,
                    'WeaponType'       => 'One-Handed Melee',
                    'WeaponRange'      => 1,
                ]),
                'cost'       => 300,
                'available'  => true,
                'version'    => '2.0',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => self::SWIFT_BOOTS_ID,
                'name'       => 'Swift Boots',
                'type'       => 'utility',
                'slot'       => 'utility',
                'properties' => json_encode(['Movement' => 1]),
                'cost'       => 150,
                'available'  => true,
                'version'    => '2.0',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('shop_items')->upsert($items, ['id'], [
            'name', 'type', 'slot', 'properties', 'cost', 'available', 'version', 'updated_at',
        ]);
    }
}
