<!-- @spec-link [[ui_shop]] -->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    ownedQty: {
        type: Number,
        default: 0
    },
    canAfford: {
        type: Boolean,
        default: true
    }
});

defineEmits(['purchase']);

const slotIcons = {
    armor: '🛡️',
    weapon: '⚔️',
    utility: '⚙️'
};

const properties = computed(() => {
    return Object.entries(props.item.properties || {}).map(([key, val]) => ({
        key,
        label: key.replace(/([A-Z])/g, ' $1').trim(),
        value: val
    }));
});
</script>

<template>
    <div 
        class="p-4 bg-upsilon-gunmetal/30 border border-upsilon-steel/20 hover:border-upsilon-cyan/40 transition-all relative group flex flex-col h-full shadow-lg backdrop-blur-md"
        :class="{ 'opacity-60': !item.available }"
    >
        <!-- Accent corners -->
        <div class="absolute top-0 left-0 w-2 h-2 border-t-2 border-l-2 border-upsilon-cyan/40 group-hover:border-upsilon-cyan transition-colors"></div>
        <div class="absolute bottom-0 right-0 w-2 h-2 border-b-2 border-r-2 border-upsilon-cyan/40 group-hover:border-upsilon-cyan transition-colors"></div>

        <div class="flex justify-between items-start mb-3">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 flex items-center justify-center bg-black/60 border border-upsilon-steel/20 text-[12px]">
                    {{ slotIcons[item.slot] || '📦' }}
                </div>
                <div>
                    <h3 class="font-scifi text-[11px] text-white tracking-widest uppercase">{{ item.name }}</h3>
                    <div class="text-[7px] text-upsilon-steel font-mono uppercase">{{ item.slot }}</div>
                </div>
            </div>
            <div v-if="ownedQty > 0" class="px-1.5 py-0.5 bg-upsilon-cyan/10 border border-upsilon-cyan/30 text-upsilon-cyan font-mono text-[8px] uppercase">
                Owned: {{ ownedQty }}
            </div>
        </div>

        <div class="flex-1 space-y-1 mb-4">
            <div 
                v-for="prop in properties" 
                :key="prop.key"
                class="flex justify-between items-center text-[9px] font-mono border-b border-upsilon-steel/5 py-0.5"
            >
                <span class="text-upsilon-steel uppercase">{{ prop.label }}</span>
                <span class="text-white">{{ prop.value }}</span>
            </div>
        </div>

        <div class="flex items-center justify-between mt-auto pt-3 border-t border-upsilon-steel/10">
            <div class="flex items-center gap-1">
                <span class="text-upsilon-lime font-mono text-[11px]">{{ item.cost }}</span>
                <span class="text-upsilon-steel text-[7px] font-scifi uppercase">Credits</span>
            </div>
            
            <button 
                v-if="item.available"
                @click="$emit('purchase', item)"
                :disabled="!canAfford"
                class="px-3 py-1.5 font-scifi text-[9px] uppercase tracking-wider transition-all"
                :class="canAfford 
                    ? 'bg-upsilon-cyan/20 border border-upsilon-cyan/40 text-upsilon-cyan hover:bg-upsilon-cyan hover:text-black shadow-glow-cyan' 
                    : 'bg-upsilon-steel/10 border border-upsilon-steel/20 text-upsilon-steel cursor-not-allowed'"
            >
                {{ canAfford ? 'Acquire' : 'Low Funds' }}
            </button>
            <div v-else class="text-upsilon-rust font-scifi text-[9px] uppercase">Sold Out</div>
        </div>
    </div>
</template>
