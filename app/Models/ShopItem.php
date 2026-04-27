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
        'skill_template_id',
        'version',
    ];

    protected $casts = [
        'properties' => 'array',
        'available'  => 'bool',
        'cost'       => 'integer',
    ];

    /**
     * Exotic items only — null for vanilla weapons/armor (D11).
     * @spec-link [[mec_item_carried_skill]]
     */
    public function skillTemplate()
    {
        return $this->belongsTo(SkillTemplate::class, 'skill_template_id');
    }
}
