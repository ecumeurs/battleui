import auth from './auth';

/**
 * @spec-link [[api_inventory_list]]
 * @spec-link [[api_equipment_management]]
 */
class InventoryService {
    async listInventory() {
        return await auth.get('/profile/inventory');
    }

    async getEquipment(characterId) {
        return await auth.get(`/profile/character/${characterId}/equipment`);
    }

    async equip(characterId, itemId) {
        return await auth.post(`/profile/character/${characterId}/equip`, {
            item_id: itemId
        });
    }

    async unequip(characterId, slot) {
        return await auth.delete(`/profile/character/${characterId}/unequip/${slot}`);
    }
}

export default new InventoryService();
