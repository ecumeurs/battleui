<!-- @spec-link [[ui_character_full_stat_panel]] -->
<script setup>
defineProps({
    stat: {
        type: Object,
        required: true
    },
    canUpgrade: {
        type: Boolean,
        default: false
    }
});

defineEmits(['upgrade']);
</script>

<template>
    <div class="flex justify-between items-center py-1 border-b border-upsilon-steel/10 group/row">
        <div class="flex items-center gap-2">
            <span class="font-scifi text-[9px] tracking-wider" :class="stat.classA ? 'text-upsilon-lime' : 'text-upsilon-steel'">
                {{ stat.label }}
            </span>
            <span v-if="!stat.classA" class="text-[7px] text-upsilon-steel/60 uppercase font-mono">(Item only)</span>
        </div>
        
        <div class="flex items-center gap-2 font-mono text-[10px]">
            <span class="text-white/40">{{ stat.base }}</span>
            <span v-if="stat.bonus > 0" class="text-upsilon-cyan">+{{ stat.bonus }}</span>
            <span class="text-white min-w-[20px] text-right">{{ stat.effective }}</span>
            
            <button 
                v-if="canUpgrade && stat.classA" 
                @click="$emit('upgrade', stat.key)"
                class="opacity-0 group-hover/row:opacity-100 ml-1 text-upsilon-lime hover:scale-125 transition-all"
                :title="`Upgrade (${stat.cpCost} CP)`"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>
</template>
