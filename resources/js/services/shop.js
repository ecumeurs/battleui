import auth from './auth';

/**
 * @spec-link [[api_shop_browse]]
 * @spec-link [[api_shop_purchase]]
 */
class ShopService {
    async listItems() {
        return await auth.get('/shop/items');
    }

    async purchase(shopItemId, quantity = 1) {
        return await auth.post('/shop/purchase', {
            shop_item_id: shopItemId,
            quantity
        });
    }
}

export default new ShopService();
