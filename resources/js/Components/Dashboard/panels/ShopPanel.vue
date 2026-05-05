<!-- Shop panel: item list left, detail + inline purchase confirm right.
     @spec-link [[ui_shop]] -->
<script setup>
import { ref, computed } from 'vue';
import { useDashboardState } from '@/Composables/useDashboardState';
import ShopItemDetail from '@/Components/Shop/ShopItemDetail.vue';
import shopService from '@/services/shop';

const props = defineProps({
    user: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const { updateUser, refresh: refreshState } = useDashboardState();

const items      = ref([]);
const loading    = ref(false);
const selected   = ref(null);
const showConfirm = ref(false);
const purchasing  = ref(false);
const errorMsg    = ref('');

const userCredits = computed(() => props.user?.credits ?? 0);

const slotIcons  = { armor: '◈', weapon: '⚔', utility: '◉' };
const slotColors = { armor: 'text-upsilon-cyan', weapon: 'text-upsilon-magenta', utility: 'text-upsilon-lime' };

async function load() {
    loading.value = true;
    try {
        const result = await shopService.listItems();
        items.value = Array.isArray(result) ? result : [];
    } finally {
        loading.value = false;
    }
}

load();

function selectItem(item) {
    selected.value = item;
    showConfirm.value = false;
    errorMsg.value = '';
}

function requestPurchase() {
    errorMsg.value = '';
    showConfirm.value = true;
}

async function confirmPurchase() {
    if (!selected.value) return;
    purchasing.value = true;
    errorMsg.value = '';
    try {
        const data = await shopService.purchase(selected.value.id, 1);
        const newCredits = data?.credits ?? (userCredits.value - selected.value.cost);
        updateUser({ credits: newCredits });
        await refreshState();
        showConfirm.value = false;
    } catch (err) {
        errorMsg.value = err?.message ?? err?.data?.message ?? 'Transaction failed. Try again.';
        showConfirm.value = false;
    } finally {
        purchasing.value = false;
    }
}
</script>

<template>
    <div class="flex flex-col flex-1 overflow-hidden">
        <!-- Corner accent -->
        <div class="absolute top-0 left-0 w-4 h-4 border-t-2 border-l-2 border-upsilon-cyan/60 pointer-events-none"></div>

        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-upsilon-cyan/20 shrink-0">
            <span class="font-scifi text-ui-sm uppercase tracking-[0.3em] text-upsilon-cyan">
                ◈ Supply Depot
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
                    Authorized catalog — acquire assets with credits
                </div>

                <div v-if="loading" class="space-y-2 animate-pulse">
                    <div v-for="i in 8" :key="i" class="h-12 bg-upsilon-gunmetal/40 border border-upsilon-steel/20"></div>
                </div>

                <div v-else-if="items.length === 0" class="py-8 text-center border border-dashed border-upsilon-steel/20">
                    <span class="font-mono text-ui-xs text-upsilon-steel uppercase">Catalog unavailable.</span>
                </div>

                <div v-else class="space-y-1">
                    <button
                        v-for="item in items"
                        :key="item.id"
                        @click="selectItem(item)"
                        data-testid="shop-item"
                        class="w-full text-left px-3 py-2.5 border"
                        :class="selected?.id === item.id
                            ? 'bg-upsilon-magenta/10 border-upsilon-magenta/50'
                            : 'bg-black/30 border-upsilon-steel/20 hover:border-upsilon-cyan/30 hover:bg-upsilon-gunmetal/40'"
                        style="transition: all 150ms;"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span :class="slotColors[item.slot] || 'text-upsilon-steel'">
                                    {{ slotIcons[item.slot] || '◆' }}
                                </span>
                                <div>
                                    <div class="font-scifi text-ui-sm text-white uppercase tracking-widest truncate max-w-[140px]">
                                        {{ item.name }}
                                    </div>
                                    <div class="font-mono text-ui-xs uppercase"
                                        :class="slotColors[item.slot] || 'text-upsilon-steel'">
                                        {{ item.slot }}
                                    </div>
                                </div>
                            </div>
                            <span class="font-scifi text-ui-sm text-upsilon-cyan shrink-0">{{ item.cost }} CR</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- RIGHT: Detail + confirm -->
            <div class="flex-1 overflow-y-auto p-6 flex flex-col gap-4">
                <div v-if="!selected" class="flex-1 flex items-center justify-center">
                    <p class="font-mono text-ui-sm text-upsilon-cyan/40 uppercase tracking-widest text-center">
                        SELECT AN ASSET<br/>TO VIEW SPECIFICATIONS
                    </p>
                </div>

                <template v-else>
                    <ShopItemDetail
                        :item="selected"
                        :user-credits="userCredits"
                        @purchase="requestPurchase"
                    />

                    <!-- Error -->
                    <div v-if="errorMsg" class="px-3 py-2 border border-upsilon-magenta/50 bg-upsilon-magenta/10 font-mono text-ui-xs text-upsilon-magenta uppercase">
                        ⚠ {{ errorMsg }}
                    </div>

                    <!-- Inline confirm banner -->
                    <div v-if="showConfirm" class="border border-upsilon-cyan/30 bg-upsilon-cyan/5 p-4 space-y-3">
                        <div class="font-mono text-ui-xs text-upsilon-cyan uppercase tracking-[0.3em]">Transaction Validation</div>
                        <div class="font-mono text-ui-sm text-upsilon-lime">
                            Confirm acquisition of <span class="text-white font-bold">{{ selected.name }}</span>
                            for <span class="text-upsilon-cyan font-bold">{{ selected.cost }} CR</span>?
                        </div>
                        <div class="flex gap-3">
                            <button
                                :disabled="purchasing"
                                @click="confirmPurchase"
                                class="px-6 py-2 border border-upsilon-cyan text-upsilon-cyan font-scifi text-ui-xs uppercase hover:bg-upsilon-cyan hover:text-black disabled:opacity-40"
                                style="transition: all 150ms;"
                            >
                                {{ purchasing ? 'PROCESSING…' : 'Acquire' }}
                            </button>
                            <button
                                :disabled="purchasing"
                                @click="showConfirm = false"
                                class="px-6 py-2 border border-upsilon-steel/30 text-upsilon-steel font-scifi text-ui-xs uppercase hover:bg-upsilon-steel/10 disabled:opacity-40"
                            >
                                Abort
                            </button>
                        </div>
                    </div>
                </template>
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
