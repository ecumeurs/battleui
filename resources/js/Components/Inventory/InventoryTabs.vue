<!-- @spec-link [[ui_inventory]] -->
<script setup>
const props = defineProps({
    activeTab: {
        type: String,
        default: 'all'
    }
});

defineEmits(['change']);

const tabs = [
    { id: 'all', label: 'All Manifests' },
    { id: 'armor', label: 'Armor' },
    { id: 'weapon', label: 'Armament' },
    { id: 'utility', label: 'Utility' }
];
</script>

<template>
    <div class="flex gap-1 border-b border-upsilon-steel/20 mb-6">
        <button 
            v-for="tab in tabs" 
            :key="tab.id"
            @click="$emit('change', tab.id)"
            class="px-4 py-2 font-scifi text-[9px] uppercase tracking-widest transition-all relative overflow-hidden"
            :class="activeTab === tab.id ? 'text-upsilon-cyan bg-upsilon-cyan/5' : 'text-upsilon-steel hover:text-white'"
        >
            {{ tab.label }}
            <div v-if="activeTab === tab.id" class="absolute bottom-0 left-0 w-full h-0.5 bg-upsilon-cyan shadow-glow-cyan"></div>
        </button>
    </div>
</template>
