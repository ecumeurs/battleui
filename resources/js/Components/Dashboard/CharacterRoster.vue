<!-- @spec-link [[ui_character_roster]] -->
<!-- @spec-link [[mechanic_mech_frontend_auth_bridge]] -->
<script setup>
import { useDashboardState } from '@/Composables/useDashboardState';
import CharacterCard from '../Character/CharacterCard.vue';

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

const { characters, initialized } = useDashboardState();
</script>

<template>
    <div class="p-5 bg-upsilon-gunmetal/20 border border-upsilon-steel/30 backdrop-blur-sm relative group">
        <div class="absolute -top-px -left-px w-2 h-2 border-t border-l border-upsilon-cyan"></div>
        <div class="absolute -bottom-px -right-px w-2 h-2 border-b border-r border-upsilon-cyan"></div>

        <h2 class="font-scifi text-ui-sm text-upsilon-lime uppercase tracking-[0.3em] mb-6 flex justify-between">
            Combatant Roster
            <span class="text-upsilon-lime" v-if="initialized">Active</span>
            <span class="text-upsilon-cyan animate-pulse" v-else>Synchronizing...</span>
        </h2>

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
                />
            </div>
        </div>

        <!-- Loading Skeleton -->
        <div v-else class="grid grid-cols-1 gap-6 animate-pulse">
            <div v-for="i in 2" :key="i" class="h-48 bg-upsilon-gunmetal/40 border border-upsilon-steel/20"></div>
        </div>
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
