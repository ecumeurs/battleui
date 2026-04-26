<!-- @spec-link [[ui_character_roster]] -->
<!-- @spec-link [[mechanic_mech_frontend_auth_bridge]] -->
<!-- @spec-link [[rule_character_renaming]] -->
<script setup>
import { ref, onMounted } from 'vue';
import auth from '@/services/auth';
import inventoryService from '@/services/inventory';
import CharacterCard from './Character/CharacterCard.vue';
import EquipDrawer from './Inventory/EquipDrawer.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
});

const characters = ref([]);
const inventory = ref([]);
const loading = ref(true);
const error = ref(null);

const showEquipDrawer = ref(false);
const selectedCharacter = ref(null);
const activeSlot = ref(null);

const handleRename = async ({ id, name }) => {
    try {
        const data = await auth.post(`/profile/character/${id}/rename`, { name });
        const index = characters.value.findIndex(c => c.id === id);
        if (index !== -1) {
            characters.value[index] = data;
        }
    } catch (err) {
        alert(err.message || 'Rename failed');
    }
};

const fetchData = async () => {
    loading.value = true;
    try {
        const [charsData, invData] = await Promise.all([
            auth.get('/profile/characters'),
            inventoryService.listInventory()
        ]);
        characters.value = charsData;
        inventory.value = invData;
    } catch (err) {
        error.value = 'Failed to load character roster.';
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const handleReroll = async (characterId) => {
    if (!confirm('Are you sure you want to reroll this character? It will reset all stats to baseline.')) return;
    
    try {
        const data = await auth.post(`/profile/character/${characterId}/reroll`);
        const index = characters.value.findIndex(c => c.id === characterId);
        if (index !== -1) {
            characters.value[index] = data.character;
        }
    } catch (err) {
        alert(err.message || 'Reroll failed');
    }
};

const handleUpgrade = async ({ id, stat }) => {
    try {
        const payload = { stats: {} };
        payload.stats[stat] = 1;

        const data = await auth.post(`/profile/character/${id}/upgrade`, payload);
        const index = characters.value.findIndex(c => c.id === id);
        if (index !== -1) {
            characters.value[index] = data;
        }
    } catch (err) {
        alert(err.message || 'Upgrade failed');
    }
};

const handleManageEquipment = ({ id, slot }) => {
    const char = characters.value.find(c => c.id === id);
    if (char) {
        selectedCharacter.value = char;
        activeSlot.value = slot;
        showEquipDrawer.value = true;
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

onMounted(fetchData);
</script>

<template>
    <div class="p-5 bg-upsilon-gunmetal/20 border border-upsilon-steel/30 backdrop-blur-sm relative group">
        <div class="absolute -top-px -left-px w-2 h-2 border-t border-l border-upsilon-cyan"></div>
        <div class="absolute -bottom-px -right-px w-2 h-2 border-b border-r border-upsilon-cyan"></div>
        
        <h2 class="font-scifi text-[10px] text-upsilon-lime uppercase tracking-[0.3em] mb-6 flex justify-between">
            Combatant Roster
            <span class="text-upsilon-lime" v-if="!loading">Active</span>
            <span class="text-upsilon-cyan animate-pulse" v-else>Synchronizing...</span>
        </h2>

        <div v-if="error" class="p-4 bg-upsilon-magenta/10 border border-upsilon-magenta/30 text-upsilon-magenta font-mono text-[9px] uppercase">
            {{ error }}
        </div>

        <div v-if="!loading" class="grid grid-cols-1 gap-6">
            <CharacterCard 
                v-for="char in characters" 
                :key="char.id" 
                :character="char"
                :user="props.user"
                @rename="handleRename"
                @reroll="handleReroll"
                @upgrade="handleUpgrade"
                @manage-equipment="handleManageEquipment"
            />
        </div>

        <!-- Loading Skeleton -->
        <div v-else class="grid grid-cols-1 gap-6 animate-pulse">
            <div v-for="i in 2" :key="i" class="h-64 bg-upsilon-gunmetal/40 border border-upsilon-steel/20"></div>
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
    </div>
</template>


<style scoped>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
