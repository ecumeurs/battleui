<!-- @spec-link [[ui_character_equipment_panel]] -->
<script setup>
const props = defineProps({
    slot: {
        type: String,
        required: true
    },
    item: {
        type: Object,
        default: null
    }
});

const slotIcons = {
    armor: '🛡️',
    weapon: '⚔️',
    utility: '⚙️'
};
</script>

<template>
    <div 
        @click="$emit('click')"
        class="flex items-center gap-2 p-1.5 bg-black/40 border border-upsilon-steel/20 cursor-pointer hover:border-upsilon-cyan/60 transition-all group/slot relative overflow-hidden"
    >
        <div class="absolute -top-px -left-px w-1 h-1 border-t border-l border-upsilon-steel/40 group-hover/slot:border-upsilon-cyan"></div>
        
        <div class="w-5 h-5 flex items-center justify-center bg-upsilon-gunmetal text-[10px] grayscale group-hover/slot:grayscale-0 transition-all">
            {{ slotIcons[slot] }}
        </div>
        
        <div class="flex-1 min-w-0">
            <div class="text-[7px] text-upsilon-steel uppercase font-mono tracking-tighter">{{ slot }}</div>
            <div 
                class="text-[9px] truncate font-scifi uppercase"
                :class="item ? 'text-white' : 'text-upsilon-steel/40'"
            >
                {{ item?.shop_item?.name || 'Empty Slot' }}
            </div>
        </div>

        <div v-if="item" class="text-upsilon-cyan opacity-0 group-hover/slot:opacity-100 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
        </div>
    </div>
</template>
