<?php

namespace App\Http\Resources\API\Upsilon;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @spec-link [[entity_character]]
 */
class UpsilonEntityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this is the Character model or an array/object matching the structure
        /**
         * NOTE: This resource is used primarily to transmit baseline character data TOWARD the Upsilon engine.
         * The 'hp' field in the database represents a character's maximum potential (Max HP). 
         * Since current HP is a consumable stat managed during battle and not persisted in the database roster, 
         * we map both 'hp' and 'max_hp' to this baseline value for engine initialization.
         */
        $equippedItems = [];
        if ($this->resource instanceof \Illuminate\Database\Eloquent\Model && $this->relationLoaded('equipment') && $this->equipment) {
            $slots = ['armor', 'utility', 'weapon'];
            foreach ($slots as $slot) {
                $itemRelation = $slot . 'Item';
                if ($this->equipment->relationLoaded($itemRelation) && $this->equipment->$itemRelation) {
                    $inventoryItem = $this->equipment->$itemRelation;
                    if ($inventoryItem->relationLoaded('shopItem') && $inventoryItem->shopItem) {
                        $shopItem = $inventoryItem->shopItem;
                        $equippedItems[] = [
                            'item_id' => $inventoryItem->id,
                            'name' => $shopItem->name,
                            'slot' => $shopItem->slot,
                            'properties' => $shopItem->properties,
                        ];
                    }
                }
            }
        }

        // Collect equipped skills: inventory rows + item-derived skills (D11)
        // @spec-link [[api_character_skill_inventory]]
        // @spec-link [[mec_skill_payload_resolution]]
        $equippedSkills = [];

        if ($this->resource instanceof \Illuminate\Database\Eloquent\Model && $this->relationLoaded('equippedSkills')) {
            foreach ($this->equippedSkills as $cs) {
                $data = $cs->instance_data ?? [];
                $equippedSkills[] = [
                    'skill_id'  => $data['id'] ?? (string) $cs->id,
                    'name'      => $data['name'] ?? 'Unknown',
                    'behavior'  => $data['behavior'] ?? 'Direct',
                    'targeting' => $data['targeting'] ?? [],
                    'costs'     => $data['costs'] ?? [],
                    'effect'    => $data['effect'] ?? [],
                    'origin'    => 'inventory',
                ];
            }
        }

        // D11: exotic items that carry a skill_template add their skill to the payload.
        if ($this->resource instanceof \Illuminate\Database\Eloquent\Model && $this->relationLoaded('equipment') && $this->equipment) {
            $slots = ['armor', 'utility', 'weapon'];
            foreach ($slots as $slot) {
                $itemRelation = $slot . 'Item';
                if (! $this->equipment->relationLoaded($itemRelation) || ! $this->equipment->$itemRelation) {
                    continue;
                }
                $inventoryItem = $this->equipment->$itemRelation;
                if (! $inventoryItem->relationLoaded('shopItem') || ! $inventoryItem->shopItem) {
                    continue;
                }
                $shopItem = $inventoryItem->shopItem;
                if (! $shopItem->skill_template_id) {
                    continue;
                }
                if (! $shopItem->relationLoaded('skillTemplate') || ! $shopItem->skillTemplate) {
                    continue;
                }
                $tpl = $shopItem->skillTemplate;
                $equippedSkills[] = [
                    'skill_id'  => $tpl->id,
                    'name'      => $tpl->name,
                    'behavior'  => $tpl->behavior,
                    'targeting' => $tpl->targeting ?? [],
                    'costs'     => $tpl->costs ?? [],
                    'effect'    => $tpl->effect ?? [],
                    'origin'    => 'item:' . $inventoryItem->id,
                ];
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'hp' => $this->hp,
            'max_hp' => $this->hp,
            'attack' => $this->attack,
            'defense' => $this->defense,
            'move' => $this->movement,
            'max_move' => $this->movement,
            'position' => $this->position ?? null,
            'equipped_items' => $equippedItems,
            'equipped_skills' => $equippedSkills,
        ];
    }
}
