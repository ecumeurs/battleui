<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Admin\StoreShopItemRequest;
use App\Http\Requests\API\Admin\UpdateShopItemRequest;
use App\Http\Resources\ShopItemResource;
use App\Models\ShopItem;

/**
 * @spec-link [[api_shop_item_admin_crud]]
 * @spec-link [[rule_admin_content_authority]]
 */
class AdminShopItemController extends Controller
{
    public function index()
    {
        $items = ShopItem::orderBy('created_at', 'desc')->get();

        return $this->success(
            ShopItemResource::collection($items),
            'Shop items retrieved.',
        );
    }

    public function show(string $id)
    {
        $item = ShopItem::findOrFail($id);

        return $this->success(
            new ShopItemResource($item),
            'Shop item retrieved.',
        );
    }

    public function store(StoreShopItemRequest $request)
    {
        $item = ShopItem::create($request->validated());

        return $this->success(
            new ShopItemResource($item),
            'Shop item created.',
            201,
        );
    }

    public function update(UpdateShopItemRequest $request, string $id)
    {
        $item = ShopItem::findOrFail($id);
        $item->update($request->validated());

        return $this->success(
            new ShopItemResource($item->fresh()),
            'Shop item updated.',
        );
    }

    public function destroy(string $id)
    {
        $item = ShopItem::findOrFail($id);
        $item->delete();

        return $this->success(null, 'Shop item deleted.');
    }
}
