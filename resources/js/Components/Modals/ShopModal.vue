<!-- Shop browse modal: list on left, detail (cost + purchase CTA) on right.
     @spec-link [[ui_shop]] -->
<script setup>
import { ref, computed, watch } from 'vue';
import ListDetailModal from '@/Components/Modals/ListDetailModal.vue';
import ShopItemDetail from '@/Components/Shop/ShopItemDetail.vue';
import PurchaseConfirmModal from '@/Components/Shop/PurchaseConfirmModal.vue';
import shopService from '@/services/shop';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    user: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'credits-updated']);

const items = ref([]);
const loading = ref(false);
const selected = ref(null);
const showConfirm = ref(false);
const purchasing = ref(false);
const errorMsg = ref('');

const userCredits = computed(() => props.user?.credits ?? 0);

const slotIcons = { armor: '◈', weapon: '⚔', utility: '◉' };
const slotColors = {
    armor:   'text-upsilon-cyan',
    weapon:  'text-upsilon-magenta',
    utility: 'text-upsilon-lime',
};

async function load() {
    loading.value = true;
    try {
        const result = await shopService.listItems();
        items.value = Array.isArray(result) ? result : [];
    } finally {
        loading.value = false;
    }
}

watch(() => props.show, (v) => {
    if (v) {
        selected.value = null;
        errorMsg.value = '';
        load();
    }
});

function requestPurchase(item) {
    errorMsg.value = '';
    showConfirm.value = true;
}

async function confirmPurchase() {
    if (!selected.value) return;
    purchasing.value = true;
    errorMsg.value = '';
    try {
        const res = await shopService.purchase(selected.value.id, 1);
        const newCredits = res.data?.data?.credits ?? userCredits.value;
        emit('credits-updated', newCredits);
        showConfirm.value = false;
    } catch (err) {
        errorMsg.value = err?.response?.data?.message ?? 'Transaction failed. Try again.';
        showConfirm.value = false;
    } finally {
        purchasing.value = false;
    }
}
</script>

<template>
    <ListDetailModal
        :show="show"
        title="Supply Depot"
        subtitle="Authorized catalog — acquire assets with credits"
        :has-detail="!!selected"
        detail-placeholder="Select an asset to view specifications and pricing."
        @close="$emit('close')"
    >
        <template #list>
            <div v-if="loading" class="space-y-2 animate-pulse">
                <div v-for="i in 8" :key="i" class="h-12 bg-upsilon-gunmetal/40 border border-upsilon-steel/20"></div>
            </div>

            <div v-else-if="items.length === 0" class="py-8 text-center border border-dashed border-upsilon-steel/20">
                <span class="font-mono text-[9px] text-upsilon-steel uppercase">Catalog unavailable.</span>
            </div>

            <div v-else class="space-y-1">
                <button
                    v-for="item in items"
                    :key="item.id"
                    @click="selected = item"
                    data-testid="shop-item"
                    class="w-full text-left px-3 py-2.5 border transition-all duration-150 group"
                    :class="selected?.id === item.id
                        ? 'bg-upsilon-magenta/10 border-upsilon-magenta/50'
                        : 'bg-black/30 border-upsilon-steel/20 hover:border-upsilon-cyan/30 hover:bg-upsilon-gunmetal/40'"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span :class="slotColors[item.slot] || 'text-upsilon-steel'">
                                {{ slotIcons[item.slot] || '◆' }}
                            </span>
                            <div>
                                <div class="font-scifi text-[10px] text-white uppercase tracking-widest truncate max-w-[130px]">
                                    {{ item.name }}
                                </div>
                                <div class="font-mono text-[7px] uppercase"
                                    :class="slotColors[item.slot] || 'text-upsilon-steel'">
                                    {{ item.slot }}
                                </div>
                            </div>
                        </div>
                        <span class="font-scifi text-[10px] text-upsilon-cyan shrink-0">{{ item.cost }} CR</span>
                    </div>
                </button>
            </div>
        </template>

        <template #detail>
            <div class="space-y-4">
                <ShopItemDetail
                    :item="selected"
                    :user-credits="userCredits"
                    @purchase="requestPurchase"
                />
                <div v-if="errorMsg" class="px-3 py-2 border border-upsilon-magenta/50 bg-upsilon-magenta/10 font-mono text-[9px] text-upsilon-magenta uppercase">
                    ⚠ {{ errorMsg }}
                </div>
            </div>
        </template>
    </ListDetailModal>

    <PurchaseConfirmModal
        :show="showConfirm"
        :item="selected"
        @close="showConfirm = false"
        @confirm="confirmPurchase"
    />
</template>
