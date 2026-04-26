<!-- @spec-link [[ui_inventory]] -->
<script setup>
const props = defineProps({
    item: {
        type: Object,
        required: true
    }
});

defineEmits(['equip', 'unequip']);

const slotIcons = {
    armor: '🛡️',
    weapon: '⚔️',
    utility: '⚙️'
};
</script>

<template>
    <div class="flex items-center justify-between p-3 bg-black/40 border border-upsilon-steel/20 hover:border-upsilon-cyan/30 transition-all group relative">
        <div class="flex items-center gap-4">
            <div class="w-8 h-8 flex items-center justify-center bg-upsilon-gunmetal border border-upsilon-steel/20 text-sm">
                {{ slotIcons[item.shop_item.slot] || '📦' }}
            </div>
            
            <div>
                <h4 class="font-scifi text-[11px] text-white tracking-widest uppercase">{{ item.shop_item.name }}</h4>
                <div class="flex gap-3 mt-0.5">
                    <span class="text-[7px] text-upsilon-steel font-mono uppercase">{{ item.shop_item.slot }}</span>
                    <span class="text-[7px] text-upsilon-cyan font-mono uppercase">Qty: {{ item.quantity }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div v-if="item.equipped_on" class="flex flex-col items-end mr-4">
                <span class="text-[7px] text-upsilon-steel font-mono uppercase">Linked to</span>
                <span class="text-upsilon-lime font-scifi text-[9px] uppercase tracking-wider">{{ item.equipped_on.name }}</span>
            </div>
            
            <button 
                v-if="!item.equipped_on"
                @click="$emit('equip', item)"
                class="px-3 py-1 border border-upsilon-cyan/40 text-upsilon-cyan font-scifi text-[8px] uppercase hover:bg-upsilon-cyan hover:text-black transition-all"
            >
                Link
            </button>
            <button 
                v-else
                @click="$emit('unequip', item)"
                class="px-3 py-1 border border-upsilon-magenta/40 text-upsilon-magenta font-scifi text-[8px] uppercase hover:bg-upsilon-magenta hover:text-white transition-all"
            >
                Sever
            </button>
        </div>
    </div>
</template>
