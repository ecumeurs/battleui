<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import inventoryService from '@/services/inventory';
import auth from '@/services/auth';
import InventoryList from '@/Components/Inventory/InventoryList.vue';
import EquipDrawer from '@/Components/Inventory/EquipDrawer.vue';

const props = defineProps({
    auth: Object
});

const inventory = ref([]);
const characters = ref([]);
const loading = ref(true);

const showEquipDrawer = ref(false);
const selectedCharacter = ref(null);
const activeSlot = ref(null);

const fetchData = async () => {
    loading.value = true;
    try {
        const [invData, charsData] = await Promise.all([
            inventoryService.listInventory(),
            auth.get('/profile/characters')
        ]);
        inventory.value = invData;
        characters.value = charsData;
    } catch (err) {
        console.error('Failed to load inventory data', err);
    } finally {
        loading.value = false;
    }
};

const handleEquipRequest = (item) => {
    // If we have characters, pick the first one or open a selection
    if (characters.value.length > 0) {
        selectedCharacter.value = characters.value[0];
        activeSlot.value = item.shop_item.slot;
        showEquipDrawer.value = true;
    } else {
        alert('No active combatants detected for hardware linking.');
    }
};

const handleEquip = async ({ characterId, itemId }) => {
    try {
        await inventoryService.equip(characterId, itemId);
        await fetchData(); // Refresh everything
    } catch (err) {
        alert(err.message || 'Linking failed');
    }
};

const handleUnequip = async ({ characterId, slot }) => {
    try {
        await inventoryService.unequip(characterId, slot);
        await fetchData(); // Refresh everything
    } catch (err) {
        alert(err.message || 'Link termination failed');
    }
};

const handleGlobalUnequip = async (item) => {
    if (!item.equipped_on) return;
    await handleUnequip({ 
        characterId: item.equipped_on.id, 
        slot: item.shop_item.slot 
    });
};

onMounted(fetchData);
</script>

<template>
    <Head title="Asset Cache" />

    <TacticalLayout :user="auth.user">
        <div class="py-12 flex-1 overflow-y-auto">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-8 px-4">
                    <h2 class="font-scifi text-xl text-white uppercase tracking-[0.3em] flex items-center gap-4">
                        <span class="text-upsilon-cyan">Asset</span> Cache
                        <span class="text-[10px] text-upsilon-steel font-mono px-2 py-0.5 border border-upsilon-steel/30">Registry v2.4</span>
                    </h2>
                </div>

                <div class="bg-upsilon-gunmetal/10 backdrop-blur-xl border border-upsilon-steel/10 p-8 shadow-2xl relative">
                    <div class="mb-10 flex flex-col md:flex-row justify-between items-baseline gap-4 border-b border-upsilon-steel/20 pb-6">
                        <div>
                            <h3 class="font-scifi text-upsilon-cyan text-xs uppercase tracking-[0.2em] mb-1">Hardware.Inventory</h3>
                            <p class="text-upsilon-steel text-[10px] font-mono uppercase">Acquired assets and registered combatant links.</p>
                        </div>
                    </div>

                    <InventoryList 
                        :inventory="inventory" 
                        :loading="loading"
                        @equip="handleEquipRequest"
                        @unequip="handleGlobalUnequip"
                    />
                </div>
            </div>
        </div>

        <EquipDrawer 
            :show="showEquipDrawer"
            :character="selectedCharacter"
            :inventory="inventory"
            :active-slot="activeSlot"
            @close="showEquipDrawer = false"
            @equip="handleEquip"
            @unequip="handleUnequip"
        />
    </TacticalLayout>
</template>

