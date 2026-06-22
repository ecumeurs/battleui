<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-user owned items. Ownership only — equip state lives in
 * `character_equipment` (D1 of ISS-074).
 *
 * @spec-link [[entity_player_inventory]]
 */
class PlayerInventory extends Model
{
    use HasUuids;

    protected $table = 'player_inventory';

    protected $fillable = [
        'player_id',
        'shop_item_id',
        'quantity',
        'purchased_at',
    ];

    protected $casts = [
        'quantity'     => 'integer',
        'purchased_at' => 'datetime',
    ];

    public function player()
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    public function shopItem()
    {
        return $this->belongsTo(ShopItem::class, 'shop_item_id');
    }
}
