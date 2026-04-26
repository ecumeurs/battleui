<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryItemResource;
use App\Models\PlayerInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @spec-link [[upsilonapi:api_inventory_list]]
 */
class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $rows = PlayerInventory::with('shopItem')
            ->where('player_id', $userId)
            ->orderBy('purchased_at', 'desc')
            ->get();

        // Annotate each row with its current equip binding (if any).
        $equipBindings = DB::table('character_equipment')
            ->join('characters', 'character_equipment.character_id', '=', 'characters.id')
            ->where('characters.player_id', $userId)
            ->select(
                'characters.id as character_id',
                'characters.name as character_name',
                'character_equipment.armor_item_id',
                'character_equipment.utility_item_id',
                'character_equipment.weapon_item_id',
            )
            ->get();

        $bindingByInventoryId = [];
        foreach ($equipBindings as $b) {
            foreach (['armor_item_id' => 'armor', 'utility_item_id' => 'utility', 'weapon_item_id' => 'weapon'] as $col => $slot) {
                if ($b->{$col} !== null) {
                    $bindingByInventoryId[$b->{$col}] = [
                        'character_id'   => $b->character_id,
                        'character_name' => $b->character_name,
                        'slot'           => $slot,
                    ];
                }
            }
        }

        $rows->each(function (PlayerInventory $row) use ($bindingByInventoryId) {
            $row->equipped_on = $bindingByInventoryId[$row->id] ?? null;
        });

        return $this->success(
            InventoryItemResource::collection($rows),
            'Inventory retrieved.',
        );
    }
}
