<!-- @spec-link [[ui_shop]] -->
<script setup>
import ShopItemCard from './ShopItemCard.vue';

defineProps({
    items: {
        type: Array,
        required: true
    },
    inventory: {
        type: Array,
        default: () => []
    },
    userCredits: {
        type: Number,
        required: true
    },
    loading: {
        type: Boolean,
        default: false
    }
});

defineEmits(['purchase']);

const getOwnedQty = (shopItemId, inventory) => {
    const invItem = inventory.find(i => i.shop_item_id === shopItemId);
    return invItem ? invItem.quantity : 0;
};
</script>

<template>
    <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 animate-pulse">
        <div v-for="i in 4" :key="i" class="h-48 bg-upsilon-gunmetal/40 border border-upsilon-steel/20"></div>
    </div>
    
    <div v-else-if="items.length === 0" class="flex flex-col items-center justify-center py-20 border border-upsilon-steel/10 bg-upsilon-gunmetal/10">
        <div class="text-upsilon-steel text-4xl mb-4">📭</div>
        <div class="font-scifi text-upsilon-steel uppercase tracking-[0.3em]">Catalog Empty</div>
        <div class="font-mono text-[10px] text-upsilon-steel/60 mt-2">Check back after the next sector jump.</div>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <ShopItemCard 
            v-for="item in items" 
            :key="item.id" 
            :item="item"
            :owned-qty="getOwnedQty(item.id, inventory)"
            :can-afford="userCredits >= item.cost"
            @purchase="$emit('purchase', $event)"
        />
    </div>
</template>
