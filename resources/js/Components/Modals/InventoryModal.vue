<!-- Inventory browse modal: list on left, detail on right. Read-only browsing;
     equipment actions live in the Character modal. @spec-link [[ui_inventory]] -->
<script setup>
import { ref, onMounted, watch } from 'vue';
import ListDetailModal from '@/Components/Modals/ListDetailModal.vue';
import InventoryDetail from '@/Components/Inventory/InventoryDetail.vue';
import inventoryService from '@/services/inventory';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['close']);

const inventory = ref([]);
const loading = ref(false);
const selected = ref(null);

const slotIcons = { armor: '◈', weapon: '⚔', utility: '◉' };
const slotColors = {
    armor:   'text-upsilon-cyan',
    weapon:  'text-upsilon-magenta',
    utility: 'text-upsilon-lime',
};

async function load() {
    loading.value = true;
    try {
        const result = await inventoryService.listInventory();
        inventory.value = Array.isArray(result) ? result : [];
    } finally {
        loading.value = false;
    }
}

watch(() => props.show, (v) => {
    if (v) {
        selected.value = null;
        load();
    }
});
</script>

<template>
    <ListDetailModal
        :show="show"
        title="Inventory Archive"
        subtitle="Acquired assets — read-only registry"
        :has-detail="!!selected"
        detail-placeholder="Select an asset to view its specifications."
        @close="$emit('close')"
    >
        <template #list>
            <!-- Loading skeleton -->
            <div v-if="loading" class="space-y-2 animate-pulse">
                <div v-for="i in 6" :key="i" class="h-12 bg-upsilon-gunmetal/40 border border-upsilon-steel/20"></div>
            </div>

            <!-- Empty state -->
            <div v-else-if="inventory.length === 0" class="py-8 text-center border border-dashed border-upsilon-steel/20">
                <span class="font-mono text-[9px] text-upsilon-steel uppercase">No assets in inventory.</span>
            </div>

            <!-- Item list -->
            <div v-else class="space-y-1">
                <button
                    v-for="item in inventory"
                    :key="item.id"
                    @click="selected = item"
                    class="w-full text-left px-3 py-2.5 border transition-all duration-150 group"
                    :class="selected?.id === item.id
                        ? 'bg-upsilon-magenta/10 border-upsilon-magenta/50'
                        : 'bg-black/30 border-upsilon-steel/20 hover:border-upsilon-cyan/30 hover:bg-upsilon-gunmetal/40'"
                >
                    <div class="flex items-center gap-3">
                        <span class="text-sm" :class="slotColors[item.shop_item.slot] || 'text-upsilon-steel'">
                            {{ slotIcons[item.shop_item.slot] || '◆' }}
                        </span>
                        <div>
                            <div class="font-scifi text-[10px] text-white uppercase tracking-widest truncate max-w-[160px]">
                                {{ item.shop_item.name }}
                            </div>
                            <div class="flex gap-2 mt-0.5">
                                <span class="font-mono text-[7px] uppercase"
                                    :class="slotColors[item.shop_item.slot] || 'text-upsilon-steel'">
                                    {{ item.shop_item.slot }}
                                </span>
                                <span v-if="item.equipped_on" class="font-mono text-[7px] text-upsilon-lime uppercase">
                                    ● LINKED
                                </span>
                            </div>
                        </div>
                    </div>
                </button>
            </div>
        </template>

        <template #detail>
            <InventoryDetail :item="selected" />
        </template>
    </ListDetailModal>
</template>
