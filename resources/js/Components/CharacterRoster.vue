<!-- @spec-link [[ui_character_roster]] -->
<!-- @spec-link [[mechanic_mech_frontend_auth_bridge]] -->
<!-- @spec-link [[rule_character_renaming]] -->
<script setup>
import { ref, onMounted, computed } from 'vue';
import auth from '@/services/auth';

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
});

const characters = ref([]);
const loading = ref(true);
const error = ref(null);

const editingId = ref(null);
const currentName = ref('');

const startEdit = (char) => {
    editingId.value = char.id;
    currentName.value = char.name;
};

const cancelEdit = () => {
    editingId.value = null;
    currentName.value = '';
};

const handleRename = async (characterId) => {
    if (currentName.value.trim().length < 3) {
        alert('Name must be at least 3 characters.');
        return;
    }

    try {
        const data = await auth.post(`/profile/character/${characterId}/rename`, { name: currentName.value });
        const index = characters.value.findIndex(c => c.id === characterId);
        if (index !== -1) {
            characters.value[index] = data;
        }
        editingId.value = null;
    } catch (err) {
        alert(err.message || 'Rename failed');
    }
};

const fetchCharacters = async () => {
    loading.value = true;
    try {
        const data = await auth.get('/profile/characters');
        characters.value = data;
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

const handleUpgrade = async (characterId, stat) => {
    const char = characters.value.find(c => c.id === characterId);
    if (!char) return;

    // Frontend validation mirroring rule_progression
    const totalStats = char.hp + char.attack + char.defense + char.movement;
    if (totalStats >= 10 + props.user.total_wins) {
        alert('Maximum attribute allocation reached for this character level.');
        return;
    }

    if (stat === 'movement') {
        const maxMovement = char.initial_movement + Math.floor(props.user.total_wins / 5);
        if (char.movement >= maxMovement) {
            alert(`Movement upgrade restricted. Next upgrade available at ${((Math.floor(props.user.total_wins / 5) + 1) * 5)} wins.`);
            return;
        }
    }

    try {
        const payload = { stats: {} };
        payload.stats[stat] = 1;

        const data = await auth.post(`/profile/character/${characterId}/upgrade`, payload);
        const index = characters.value.findIndex(c => c.id === characterId);
        if (index !== -1) {
            characters.value[index] = data;
        }
    } catch (err) {
        alert(err.message || 'Upgrade failed');
    }
};

const canUpgrade = computed(() => {
    return (char) => {
        const totalStats = char.hp + char.attack + char.defense + char.movement;
        return totalStats < 10 + props.user.total_wins;
    };
});

onMounted(fetchCharacters);
</script>

<template>
    <div class="p-5 bg-upsilon-gunmetal/20 border border-upsilon-steel/30 backdrop-blur-sm relative group">
        <div class="absolute -top-px -left-px w-2 h-2 border-t border-l border-upsilon-cyan"></div>
        <div class="absolute -bottom-px -right-px w-2 h-2 border-b border-r border-upsilon-cyan"></div>
        <h2 class="font-scifi text-[10px] text-upsilon-lime uppercase tracking-[0.2em] mb-4 flex justify-between">
            Character Roster
            <span class="text-upsilon-lime" v-if="!loading">Ready</span>
            <span class="text-upsilon-cyan animate-pulse" v-else>Syncing...</span>
        </h2>

        <div v-if="error" class="p-4 bg-upsilon-magenta/10 border border-upsilon-magenta/30 text-upsilon-magenta font-mono text-[9px] uppercase">
            {{ error }}
        </div>

        <div v-if="!loading" class="space-y-4">
            <div v-for="char in characters" :key="char.id" class="p-4 bg-black/40 border border-upsilon-steel/20 hover:border-upsilon-cyan/40 transition-colors">
                <div class="flex justify-between items-baseline mb-3">
                    <div v-if="editingId === char.id" class="flex gap-2 items-center flex-1 mr-4">
                        <input 
                            v-model="currentName" 
                            class="bg-black/60 border border-upsilon-cyan text-white text-[10px] px-2 py-1 flex-1 font-mono focus:outline-none"
                            @keyup.enter="handleRename(char.id)"
                            @keyup.esc="cancelEdit"
                        />
                        <button @click="handleRename(char.id)" class="text-upsilon-lime font-mono text-[8px] border border-upsilon-lime/30 px-1 py-0.5 hover:bg-upsilon-lime/10">SAVE</button>
                    </div>
                    <span 
                        v-else 
                        @click="startEdit(char)" 
                        class="font-scifi text-sm text-white cursor-pointer hover:text-upsilon-cyan transition-colors"
                        title="Establish new identity"
                    >
                        {{ char.name }}
                    </span>
                    <span class="font-mono text-[9px] text-upsilon-lime uppercase">LVL {{ char.hp + char.attack + char.defense + char.movement - 9 }}</span>
                </div>
                
                <div class="grid grid-cols-2 gap-2 font-mono text-[9px]">
                    <div class="text-upsilon-lime uppercase flex justify-between group/stat">
                        HP <span class="text-white">{{ char.hp }}</span>
                        <button v-if="canUpgrade(char)" @click="handleUpgrade(char.id, 'hp')" class="hidden group-hover/stat:block text-upsilon-lime ml-1">+</button>
                    </div>
                    <div class="text-upsilon-lime uppercase flex justify-between group/stat">
                        MOV <span class="text-white">{{ char.movement }}</span>
                        <button v-if="canUpgrade(char)" @click="handleUpgrade(char.id, 'movement')" class="hidden group-hover/stat:block text-upsilon-lime ml-1">+</button>
                    </div>
                    <div class="text-upsilon-lime uppercase flex justify-between group/stat">
                        ATK <span class="text-white">{{ char.attack }}</span>
                        <button v-if="canUpgrade(char)" @click="handleUpgrade(char.id, 'attack')" class="hidden group-hover/stat:block text-upsilon-lime ml-1">+</button>
                    </div>
                    <div class="text-upsilon-lime uppercase flex justify-between group/stat">
                        DEF <span class="text-white">{{ char.defense }}</span>
                        <button v-if="canUpgrade(char)" @click="handleUpgrade(char.id, 'defense')" class="hidden group-hover/stat:block text-upsilon-lime ml-1">+</button>
                    </div>
                </div>
                
                <div class="mt-4 flex gap-2">
                    <button 
                        v-if="props.user.total_wins === 0"
                        @click="handleReroll(char.id)"
                        class="flex-1 py-1.5 bg-upsilon-rust/40 border border-upsilon-rust/60 text-white font-scifi text-[8px] uppercase hover:bg-upsilon-rust transition-colors"
                    >
                        Reroll Entity Stats
                    </button>
                    <div v-if="canUpgrade(char)" class="flex-1 text-center py-1.5 border border-upsilon-magenta/30 bg-upsilon-magenta/5">
                        <span class="font-scifi text-[8px] text-upsilon-magenta uppercase animate-pulse">Points Available</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Skeleton -->
        <div v-else class="space-y-4 animate-pulse">
            <div v-for="i in 3" :key="i" class="h-32 bg-upsilon-gunmetal/40 border border-upsilon-steel/20"></div>
        </div>
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
