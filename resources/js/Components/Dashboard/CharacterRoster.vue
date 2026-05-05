<!-- @spec-link [[ui_character_roster]] -->
<!-- @spec-link [[mechanic_mech_frontend_auth_bridge]] -->
<!-- @spec-link [[rule_character_renaming]] -->
<script setup>
import { ref } from 'vue';
import auth from '@/services/auth';
import inventoryService from '@/services/inventory';
import { useDashboardState } from '@/Composables/useDashboardState';
import CharacterCard from '../Character/CharacterCard.vue';
import EquipDrawer from '../Inventory/EquipDrawer.vue';
import ConfirmModal from '@/Components/Shared/Modals/ConfirmModal.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    selectedCharacterId: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['character-select']);

const { characters, inventory, loading, initialized, updateCharacter, refresh } = useDashboardState();

const error = ref(null);

const showEquipDrawer = ref(false);
const selectedCharacterForDrawer = ref(null);
const activeSlot = ref(null);
const showRerollConfirm = ref(false);
const rerollTargetId = ref(null);

const handleRename = async ({ id, name }) => {
    try {
        const data = await auth.post(`/profile/character/${id}/rename`, { name });
        updateCharacter(data);
    } catch (err) {
        alert(err.message || 'Rename failed');
    }
};

const handleReroll = (characterId) => {
    rerollTargetId.value = characterId;
    showRerollConfirm.value = true;
};

const confirmReroll = async () => {
    const characterId = rerollTargetId.value;
    showRerollConfirm.value = false;
    rerollTargetId.value = null;
    if (!characterId) return;
    try {
        const data = await auth.post(`/profile/character/${characterId}/reroll`);
        updateCharacter(data.character);
    } catch (err) {
        alert(err.message || 'Reroll failed');
    }
};

const handleUpgrade = async ({ id, stat }) => {
    try {
        const payload = { stats: {} };
        payload.stats[stat] = 1;
        const data = await auth.post(`/profile/character/${id}/upgrade`, payload);
        updateCharacter(data);
    } catch (err) {
        alert(err.message || 'Upgrade failed');
    }
};

const handleManageEquipment = ({ id, slot }) => {
    const char = characters.value.find(c => c.id === id);
    if (char) {
        selectedCharacterForDrawer.value = char;
        activeSlot.value = slot;
        showEquipDrawer.value = true;
    }
};

const handleEquip = async ({ characterId, itemId }) => {
    try {
        await inventoryService.equip(characterId, itemId);
        await refresh();
    } catch (err) {
        alert(err.message || 'Linking failed');
    }
};

const handleUnequip = async ({ characterId, slot }) => {
    try {
        await inventoryService.unequip(characterId, slot);
        await refresh();
    } catch (err) {
        alert(err.message || 'Link termination failed');
    }
};
</script>

<template>
    <div class="p-5 bg-upsilon-gunmetal/20 border border-upsilon-steel/30 backdrop-blur-sm relative group">
        <div class="absolute -top-px -left-px w-2 h-2 border-t border-l border-upsilon-cyan"></div>
        <div class="absolute -bottom-px -right-px w-2 h-2 border-b border-r border-upsilon-cyan"></div>

        <h2 class="font-scifi text-[10px] text-upsilon-lime uppercase tracking-[0.3em] mb-6 flex justify-between">
            Combatant Roster
            <span class="text-upsilon-lime" v-if="initialized">Active</span>
            <span class="text-upsilon-cyan animate-pulse" v-else>Synchronizing...</span>
        </h2>

        <div v-if="error" class="p-4 bg-upsilon-magenta/10 border border-upsilon-magenta/30 text-upsilon-magenta font-mono text-[9px] uppercase">
            {{ error }}
        </div>

        <div v-if="initialized" class="grid grid-cols-1 gap-6">
            <div
                v-for="char in characters"
                :key="char.id"
                class="cursor-pointer"
                @click="emit('character-select', char.id)"
            >
                <CharacterCard
                    :character="char"
                    :user="props.user"
                    :selected="selectedCharacterId === char.id"
                    @rename="handleRename"
                    @reroll="handleReroll"
                    @upgrade="handleUpgrade"
                    @manage-equipment="handleManageEquipment"
                />
            </div>
        </div>

        <!-- Loading Skeleton — shown only before first init completes -->
        <div v-else class="grid grid-cols-1 gap-6 animate-pulse">
            <div v-for="i in 2" :key="i" class="h-64 bg-upsilon-gunmetal/40 border border-upsilon-steel/20"></div>
        </div>

        <EquipDrawer
            :show="showEquipDrawer"
            :character="selectedCharacterForDrawer"
            :inventory="inventory"
            :active-slot="activeSlot"
            @close="showEquipDrawer = false"
            @equip="handleEquip"
            @unequip="handleUnequip"
        />

        <ConfirmModal
            :show="showRerollConfirm"
            title="Reroll Protocol"
            message="Rerolling regenerates this combatant's stats to baseline. All CP upgrades will be wiped. This cannot be undone."
            confirm-text="Reroll"
            cancel-text="Abort"
            type="warning"
            @close="showRerollConfirm = false"
            @confirm="confirmReroll"
        />
    </div>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
.animate-pulse {
    animation: pulse 2s linear infinite;
}
</style>
