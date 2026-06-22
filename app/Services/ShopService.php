<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\PlayerInventory;
use App\Models\ShopItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Shop transactions: credit deduction + inventory upsert + audit logs.
 * All-or-nothing per `[[mechanic_shop_inventory_system]]`.
 *
 * @spec-link [[api_shop_purchase]]
 * @spec-link [[mechanic_shop_inventory_system]]
 */
class ShopService
{
    public const QUANTITY_CAP = 99;

    public const ERR_INSUFFICIENT_CREDITS = 'insufficient_credits';
    public const ERR_QUANTITY_CAP         = 'quantity_cap';
    public const ERR_ITEM_UNAVAILABLE     = 'item_unavailable';

    /**
     * Purchase `quantity` units of `$shopItem` for `$user`.
     *
     * @return array{credits: int, inventory_item: PlayerInventory}
     * @throws ShopServiceException with `code` set to one of the ERR_* constants.
     */
    public function purchase(User $user, ShopItem $shopItem, int $quantity = 1): array
    {
        if ($quantity < 1) {
            throw new ShopServiceException('Quantity must be >= 1', 422, self::ERR_QUANTITY_CAP);
        }

        return DB::transaction(function () use ($user, $shopItem, $quantity) {
            // Lock the user row to serialize concurrent purchases.
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if (! $shopItem->available) {
                throw new ShopServiceException('Item is not available.', 404, self::ERR_ITEM_UNAVAILABLE);
            }

            $totalCost = $shopItem->cost * $quantity;
            if ($lockedUser->credits < $totalCost) {
                throw new ShopServiceException(
                    "Insufficient credits ({$lockedUser->credits} < {$totalCost}).",
                    422,
                    self::ERR_INSUFFICIENT_CREDITS,
                );
            }

            // Existing inventory row?
            $existing = PlayerInventory::where('player_id', $lockedUser->id)
                ->where('shop_item_id', $shopItem->id)
                ->lockForUpdate()
                ->first();

            $newQuantity = ($existing?->quantity ?? 0) + $quantity;
            if ($newQuantity > self::QUANTITY_CAP) {
                throw new ShopServiceException(
                    "Inventory quantity cap reached ({$newQuantity} > " . self::QUANTITY_CAP . ').',
                    422,
                    self::ERR_QUANTITY_CAP,
                );
            }

            // Debit credits.
            $lockedUser->credits -= $totalCost;
            $lockedUser->save();

            // Upsert inventory row.
            if ($existing) {
                $existing->quantity = $newQuantity;
                $existing->save();
                $inventoryRow = $existing;
            } else {
                $inventoryRow = PlayerInventory::create([
                    'player_id'    => $lockedUser->id,
                    'shop_item_id' => $shopItem->id,
                    'quantity'     => $quantity,
                    'purchased_at' => now(),
                ]);
            }

            // Audit: inventory transaction.
            InventoryTransaction::create([
                'player_id'        => $lockedUser->id,
                'shop_item_id'     => $shopItem->id,
                'quantity'         => $quantity,
                'credits_spent'    => $totalCost,
                'transaction_type' => 'purchase',
            ]);

            // Audit: credit ledger (matches existing credit_transactions schema).
            DB::table('credit_transactions')->insert([
                'id'         => (string) \Illuminate\Support\Str::uuid(),
                'player_id'  => $lockedUser->id,
                'amount'     => -$totalCost,
                'source'     => 'shop_purchase',
                'match_id'   => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'credits'        => (int) $lockedUser->credits,
                'inventory_item' => $inventoryRow->load('shopItem'),
            ];
        });
    }
}
