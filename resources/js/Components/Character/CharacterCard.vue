<!-- @spec-link [[ui_character_full_stat_panel]] -->
<script setup>
import { ref, computed } from 'vue';
import { useCharacterStats } from '@/Composables/useCharacterStats';
import CharacterStatPanel from './CharacterStatPanel.vue';
import CharacterEquipmentPanel from './CharacterEquipmentPanel.vue';
import CpEconomySummary from './CpEconomySummary.vue';

const props = defineProps({
    character: {
        type: Object,
        required: true
    },
    user: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['rename', 'reroll', 'upgrade', 'manage-equipment']);

const { calculateStats, calculateCp } = useCharacterStats();

const stats = computed(() => calculateStats(props.character));
const cp = computed(() => calculateCp(props.character, props.user.total_wins));

const editing = ref(false);
const nameInput = ref(props.character.name);

const startRename = () => {
    editing.value = true;
    nameInput.value = props.character.name;
};

const cancelRename = () => {
    editing.value = false;
};

const submitRename = () => {
    if (nameInput.value.trim().length < 3) return;
    emit('rename', { id: props.character.id, name: nameInput.value });
    editing.value = false;
};

const canUpgrade = computed(() => cp.value.spent < cp.value.max);
</script>

<template>
    <div 
        data-testid="character-card"
        class="p-4 bg-upsilon-gunmetal/30 border border-upsilon-steel/20 hover:border-upsilon-cyan/40 transition-all relative group shadow-lg backdrop-blur-md"
    >
        <!-- Accent corners -->
        <div class="absolute top-0 left-0 w-2 h-2 border-t-2 border-l-2 border-upsilon-cyan/40 group-hover:border-upsilon-cyan transition-colors"></div>
        <div class="absolute bottom-0 right-0 w-2 h-2 border-b-2 border-r-2 border-upsilon-cyan/40 group-hover:border-upsilon-cyan transition-colors"></div>

        <!-- Header: Name & Actions -->
        <div class="flex justify-between items-start mb-6">
            <div class="flex-1 min-w-0">
                <div v-if="editing" class="flex gap-2 items-center">
                    <input 
                        v-model="nameInput" 
                        class="bg-black/60 border border-upsilon-cyan text-white text-[10px] px-2 py-1 flex-1 font-mono focus:outline-none"
                        @keyup.enter="submitRename"
                        @keyup.esc="cancelRename"
                        autoFocus
                    />
                    <button @click.stop="submitRename" class="text-upsilon-lime font-mono text-[8px] border border-upsilon-lime/30 px-2 py-1 hover:bg-upsilon-lime/10">SAVE</button>
                    <button @click.stop="cancelRename" class="text-upsilon-steel font-mono text-[8px] border border-upsilon-steel/30 px-2 py-1 hover:bg-upsilon-steel/10">CANCEL</button>
                </div>
                <h3 
                    v-else 
                    @click="startRename"
                    class="font-scifi text-base text-white truncate cursor-pointer hover:text-upsilon-cyan transition-colors tracking-widest uppercase"
                >
                    {{ character.name }}
                </h3>
                <div class="text-[7px] text-upsilon-steel font-mono uppercase tracking-[0.2em] mt-1">
                    System ID: {{ character.id.split('-')[0] }}
                </div>
            </div>
            
            <button 
                v-if="user.total_wins === 0"
                @click.stop="$emit('reroll', character.id)"
                class="text-[8px] font-scifi text-upsilon-rust border border-upsilon-rust/40 px-2 py-1 hover:bg-upsilon-rust/10 transition-colors"
            >
                REROLL
            </button>
        </div>

        <!-- Body: Stats & Equipment -->
        <div class="grid grid-cols-5 gap-4">
            <!-- Stats Panel (Left) -->
            <div class="col-span-3">
                <div class="text-[8px] font-scifi text-upsilon-steel uppercase mb-2 border-b border-upsilon-steel/10 pb-1">Biometric Data</div>
                <CharacterStatPanel 
                    :stats="stats" 
                    :can-upgrade="canUpgrade"
                    @upgrade="$emit('upgrade', { id: character.id, stat: $event })"
                />
            </div>

            <!-- Equipment Panel (Right) -->
            <div class="col-span-2">
                <div class="text-[8px] font-scifi text-upsilon-steel uppercase mb-2 border-b border-upsilon-steel/10 pb-1">Hardware Link</div>
                <CharacterEquipmentPanel 
                    :equipment="character.equipment" 
                    @manage="$emit('manage-equipment', { id: character.id, slot: $event })"
                />
            </div>
        </div>

        <!-- Footer: CP Summary -->
        <CpEconomySummary :cp="cp" />
    </div>
</template>
