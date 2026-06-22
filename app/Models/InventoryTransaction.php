<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Audit row for every inventory mutation.
 *
 * @spec-link [[mechanic_shop_inventory_system]]
 */
class InventoryTransaction extends Model
{
    use HasUuids;

    protected $table = 'inventory_transactions';

    protected $fillable = [
        'player_id',
        'shop_item_id',
        'quantity',
        'credits_spent',
        'transaction_type',
    ];

    protected $casts = [
        'quantity'      => 'integer',
        'credits_spent' => 'integer',
    ];

    public function shopItem()
    {
        return $this->belongsTo(ShopItem::class, 'shop_item_id');
    }
}
