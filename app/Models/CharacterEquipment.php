<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 3-slot equipment binding for a single character. Single source of truth
 * for "what a character has equipped" (D1 of ISS-074).
 *
 * @spec-link [[upsilonbattle:entity_character_equipment]]
 */
class CharacterEquipment extends Model
{
    protected $table = 'character_equipment';
    protected $primaryKey = 'character_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'character_id',
        'armor_item_id',
        'utility_item_id',
        'weapon_item_id',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class, 'character_id');
    }

    public function armorItem()
    {
        return $this->belongsTo(PlayerInventory::class, 'armor_item_id');
    }

    public function utilityItem()
    {
        return $this->belongsTo(PlayerInventory::class, 'utility_item_id');
    }

    public function weaponItem()
    {
        return $this->belongsTo(PlayerInventory::class, 'weapon_item_id');
    }

    /**
     * Returns the slot column name for a given slot enum value.
     */
    public static function slotColumn(string $slot): string
    {
        return match ($slot) {
            'armor'   => 'armor_item_id',
            'utility' => 'utility_item_id',
            'weapon'  => 'weapon_item_id',
            default   => throw new \InvalidArgumentException("Unknown slot: {$slot}"),
        };
    }
}
