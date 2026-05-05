<!-- Inventory panel: list left, detail right. Read-only browsing.
     @spec-link [[ui_inventory]] -->
<script setup>
import { ref } from 'vue';
import InventoryDetail from '@/Components/Inventory/InventoryDetail.vue';
import inventoryService from '@/services/inventory';

defineEmits(['close']);

const inventory = ref([]);
const loading   = ref(false);
const selected  = ref(null);

const slotIcons  = { armor: '◈', weapon: '⚔', utility: '◉' };
const slotColors = { armor: 'text-upsilon-cyan', weapon: 'text-upsilon-magenta', utility: 'text-upsilon-lime' };

async function load() {
    loading.value = true;
    try {
        const result = await inventoryService.listInventory();
        inventory.value = Array.isArray(result) ? result : [];
    } finally {
        loading.value = false;
    }
}

load();
</script>

<template>
    <div class="flex flex-col flex-1 overflow-hidden">
        <!-- Corner accent -->
        <div class="absolute top-0 left-0 w-4 h-4 border-t-2 border-l-2 border-upsilon-cyan/60 pointer-events-none"></div>

        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-upsilon-cyan/20 shrink-0">
            <span class="font-scifi text-ui-sm uppercase tracking-[0.3em] text-upsilon-cyan">
                ◈ Inventory Archive
            </span>
            <button
                @click="$emit('close')"
                class="font-mono text-ui-sm text-upsilon-magenta hover:text-white border border-upsilon-magenta/30 hover:border-upsilon-magenta px-2 py-0.5"
                style="transition: color 150ms linear, border-color 150ms linear;"
            >
                SEVER ✕
            </button>
        </div>

        <div class="flex-1 flex overflow-hidden">
            <!-- LEFT: Item list -->
            <div class="w-72 shrink-0 flex flex-col border-r border-upsilon-cyan/10 overflow-y-auto p-4">
                <div class="font-mono text-ui-xs text-upsilon-steel/60 uppercase tracking-widest mb-3">
                    Acquired assets — read-only registry
                </div>

                <div v-if="loading" class="space-y-2 animate-pulse">
                    <div v-for="i in 6" :key="i" class="h-12 bg-upsilon-gunmetal/40 border border-upsilon-steel/20"></div>
                </div>

                <div v-else-if="inventory.length === 0" class="py-8 text-center border border-dashed border-upsilon-steel/20">
                    <span class="font-mono text-ui-xs text-upsilon-steel uppercase">No assets in inventory.</span>
                </div>

                <div v-else class="space-y-1">
                    <button
                        v-for="item in inventory"
                        :key="item.id"
                        @click="selected = item"
                        class="w-full text-left px-3 py-2.5 border"
                        :class="selected?.id === item.id
                            ? 'bg-upsilon-magenta/10 border-upsilon-magenta/50'
                            : 'bg-black/30 border-upsilon-steel/20 hover:border-upsilon-cyan/30 hover:bg-upsilon-gunmetal/40'"
                        style="transition: all 150ms;"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-ui-md" :class="slotColors[item.shop_item.slot] || 'text-upsilon-steel'">
                                {{ slotIcons[item.shop_item.slot] || '◆' }}
                            </span>
                            <div>
                                <div class="font-scifi text-ui-sm text-white uppercase tracking-widest truncate max-w-[160px]">
                                    {{ item.shop_item.name }}
                                </div>
                                <div class="flex gap-2 mt-0.5">
                                    <span class="font-mono text-ui-xs uppercase"
                                        :class="slotColors[item.shop_item.slot] || 'text-upsilon-steel'">
                                        {{ item.shop_item.slot }}
                                    </span>
                                    <span v-if="item.equipped_on" class="font-mono text-ui-xs text-upsilon-lime uppercase">
                                        ● LINKED
                                    </span>
                                </div>
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- RIGHT: Detail -->
            <div class="flex-1 overflow-y-auto p-6">
                <div v-if="!selected" class="h-full flex items-center justify-center">
                    <p class="font-mono text-ui-sm text-upsilon-cyan/40 uppercase tracking-widest text-center">
                        SELECT AN ASSET<br/>TO VIEW SPECIFICATIONS
                    </p>
                </div>
                <InventoryDetail v-else :item="selected" />
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
.animate-pulse {
    animation: pulse 2s linear infinite;
}
</style>
