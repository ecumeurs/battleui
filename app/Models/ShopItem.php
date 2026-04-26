<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * V2.0 catalog of purchasable items.
 *
 * @spec-link [[upsilonbattle:entity_shop_item]]
 */
class ShopItem extends Model
{
    use HasUuids;

    protected $table = 'shop_items';

    protected $fillable = [
        'name',
        'type',
        'slot',
        'properties',
        'cost',
        'available',
        'version',
    ];

    protected $casts = [
        'properties' => 'array',
        'available'  => 'bool',
        'cost'       => 'integer',
    ];
}
