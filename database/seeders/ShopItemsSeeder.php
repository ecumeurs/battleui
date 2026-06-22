<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed the V2.0 shop catalog.
 *
 * @spec-link [[entity_shop_item]]
 * @spec-link [[rule_item_pricing_simple]]
 *
 * Three items, deterministic UUIDs (test stability), fixed costs per V2.0.
 */
class ShopItemsSeeder extends Seeder
{
    public const BASIC_ARMOR_ID       = '00000000-0000-4000-8000-000000000001';
    public const BASIC_SWORD_ID       = '00000000-0000-4000-8000-000000000002';
    public const SWIFT_BOOTS_ID       = '00000000-0000-4000-8000-000000000003';
    public const REINFORCED_PLATE_ID  = '00000000-0000-4000-8000-000000000004';
    public const COMBAT_KNIFE_ID      = '00000000-0000-4000-8000-000000000005';
    public const HEAVY_HAMMER_ID      = '00000000-0000-4000-8000-000000000006';
    public const STIM_PACK_ID         = '00000000-0000-4000-8000-000000000007';
    public const TACTICAL_CLOAK_ID    = '00000000-0000-4000-8000-000000000008';

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

        $items = array_merge($items, [
            [
                'id'         => self::REINFORCED_PLATE_ID,
                'name'       => 'Reinforced Plate',
                'type'       => 'armor',
                'slot'       => 'armor',
                'properties' => json_encode(['ArmorRating' => 10]),
                'cost'       => 600,
                'available'  => true,
                'version'    => '2.0',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => self::COMBAT_KNIFE_ID,
                'name'       => 'Combat Knife',
                'type'       => 'weapon',
                'slot'       => 'weapon',
                'properties' => json_encode([
                    'WeaponBaseDamage' => 3,
                    'WeaponType'       => 'One-Handed Melee',
                    'WeaponRange'      => 1,
                ]),
                'cost'       => 100,
                'available'  => true,
                'version'    => '2.0',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => self::HEAVY_HAMMER_ID,
                'name'       => 'Heavy Hammer',
                'type'       => 'weapon',
                'slot'       => 'weapon',
                'properties' => json_encode([
                    'WeaponBaseDamage' => 8,
                    'WeaponType'       => 'Two-Handed Melee',
                    'WeaponRange'      => 1,
                ]),
                'cost'       => 800,
                'available'  => true,
                'version'    => '2.0',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => self::STIM_PACK_ID,
                'name'       => 'Stim Pack',
                'type'       => 'utility',
                'slot'       => 'utility',
                'properties' => json_encode(['HP' => 5]),
                'cost'       => 250,
                'available'  => true,
                'version'    => '2.0',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => self::TACTICAL_CLOAK_ID,
                'name'       => 'Tactical Cloak',
                'type'       => 'utility',
                'slot'       => 'utility',
                'properties' => json_encode([
                    'Movement'     => 1,
                    'Defense' => 2,
                ]),
                'cost'       => 700,
                'available'  => true,
                'version'    => '2.0',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('shop_items')->upsert($items, ['id'], [
            'name', 'type', 'slot', 'properties', 'cost', 'available', 'version', 'updated_at',
        ]);
    }
}
