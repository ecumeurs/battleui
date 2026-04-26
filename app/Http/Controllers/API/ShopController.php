<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Shop\PurchaseShopItemRequest;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\ShopItemResource;
use App\Models\ShopItem;
use App\Services\ShopService;
use App\Services\ShopServiceException;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __construct(private readonly ShopService $shop)
    {
    }

    /**
     * @spec-link [[upsilonapi:api_shop_browse]]
     */
    public function index(Request $request)
    {
        $items = ShopItem::where('available', true)->orderBy('cost')->get();

        return $this->success(
            ShopItemResource::collection($items),
            'Shop catalog retrieved.',
        );
    }

    /**
     * @spec-link [[upsilonapi:api_shop_purchase]]
     */
    public function purchase(PurchaseShopItemRequest $request)
    {
        $user     = $request->user();
        $payload  = $request->validated();
        $quantity = $payload['quantity'] ?? 1;

        $shopItem = ShopItem::find($payload['shop_item_id']);
        if (! $shopItem) {
            return $this->error('Shop item not found.', 404, null, ['reason' => 'item_unavailable']);
        }

        try {
            $result = $this->shop->purchase($user, $shopItem, $quantity);
        } catch (ShopServiceException $e) {
            return $this->error($e->getMessage(), $e->httpStatus(), null, ['reason' => $e->reason]);
        }

        return $this->success([
            'credits'        => $result['credits'],
            'inventory_item' => new InventoryItemResource($result['inventory_item']),
        ], 'Purchase complete.');
    }
}
