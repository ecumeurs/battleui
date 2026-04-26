<!-- @spec-link [[ui_inventory]] -->
<script setup>
import { ref, computed } from 'vue';
import InventoryTabs from './InventoryTabs.vue';
import InventoryRow from './InventoryRow.vue';

const props = defineProps({
    inventory: {
        type: Array,
        required: true
    },
    loading: {
        type: Boolean,
        default: false
    }
});

defineEmits(['equip', 'unequip']);

const activeTab = ref('all');

const filteredInventory = computed(() => {
    if (activeTab.value === 'all') return props.inventory;
    return props.inventory.filter(item => item.shop_item.slot === activeTab.value);
});
</script>

<template>
    <div>
        <InventoryTabs :active-tab="activeTab" @change="activeTab = $event" />

        <div v-if="loading" class="space-y-3 animate-pulse">
            <div v-for="i in 5" :key="i" class="h-14 bg-upsilon-gunmetal/40 border border-upsilon-steel/20"></div>
        </div>

        <div v-else-if="filteredInventory.length === 0" class="py-12 text-center border border-dashed border-upsilon-steel/20 bg-upsilon-gunmetal/5">
            <span class="font-mono text-[10px] text-upsilon-steel uppercase">No assets registered in this category.</span>
        </div>

        <div v-else class="space-y-3">
            <InventoryRow 
                v-for="item in filteredInventory" 
                :key="item.id" 
                :item="item"
                @equip="$emit('equip', $event)"
                @unequip="$emit('unequip', $event)"
            />
        </div>
    </div>
</template>
