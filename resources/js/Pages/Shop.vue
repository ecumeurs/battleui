<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import shopService from '@/services/shop';
import auth from '@/services/auth';
import ShopGrid from '@/Components/Shop/ShopGrid.vue';
import PurchaseConfirmModal from '@/Components/Shop/PurchaseConfirmModal.vue';

const props = defineProps({
    auth: Object
});

const items = ref([]);
const inventory = ref([]);
const loading = ref(true);
const userCredits = ref(props.auth.user.credits);

const selectedItem = ref(null);
const showConfirm = ref(false);

const fetchData = async () => {
    loading.value = true;
    try {
        const [itemsData, invData] = await Promise.all([
            shopService.listItems(),
            auth.get('/profile/inventory')
        ]);
        items.value = itemsData;
        inventory.value = invData;
    } catch (err) {
        console.error('Failed to load shop data', err);
    } finally {
        loading.value = false;
    }
};

const openConfirm = (item) => {
    selectedItem.value = item;
    showConfirm.value = true;
};

const handlePurchase = async () => {
    if (!selectedItem.value) return;
    
    try {
        const response = await shopService.purchase(selectedItem.value.id);
        
        // Update local state
        userCredits.value = response.credits;
        
        // Refresh inventory
        const invData = await auth.get('/profile/inventory');
        inventory.value = invData;
        
    } catch (err) {
        alert(err.message || 'Purchase failed');
    } finally {
        showConfirm.value = false;
        selectedItem.value = null;
    }
};

onMounted(fetchData);
</script>

<template>
    <Head title="Supply Depot" />

    <TacticalLayout :user="auth.user">
        <div class="py-12 flex-1 overflow-y-auto">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Top Stats Bar -->
                <div class="flex justify-between items-center mb-8 px-4">
                    <h2 class="font-scifi text-xl text-white uppercase tracking-[0.3em] flex items-center gap-4">
                        <span class="text-upsilon-cyan">Supply</span> Depot
                        <span class="text-[10px] text-upsilon-steel font-mono px-2 py-0.5 border border-upsilon-steel/30">Sector 7-G</span>
                    </h2>
                    
                    <div class="flex items-center gap-2">
                        <div class="flex flex-col items-end">
                            <span class="text-[8px] font-scifi text-upsilon-steel uppercase">Available Credits</span>
                            <div class="flex items-center gap-2">
                                <span class="text-upsilon-lime font-mono text-xl shadow-glow-cyan">{{ userCredits }}</span>
                                <span class="text-[10px] text-upsilon-steel font-scifi uppercase">CR</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-upsilon-gunmetal/10 backdrop-blur-xl border border-upsilon-steel/10 p-8 shadow-2xl relative">
                    <!-- Tech accents -->
                    <div class="absolute top-0 right-0 w-32 h-32 opacity-10 pointer-events-none overflow-hidden">
                        <div class="w-full h-full border-t border-r border-upsilon-cyan rotate-45 translate-x-1/2 -translate-y-1/2"></div>
                    </div>

                    <div class="mb-10 flex flex-col md:flex-row justify-between items-baseline gap-4 border-b border-upsilon-steel/20 pb-6">
                        <div>
                            <h3 class="font-scifi text-upsilon-lime text-xs uppercase tracking-[0.2em] mb-1">Catalog.Manifest</h3>
                            <p class="text-upsilon-steel text-[10px] font-mono uppercase">Authorized hardware and biometric upgrades for active combatants.</p>
                        </div>
                        
                        <div class="flex gap-4">
                            <div class="flex items-center gap-2 text-[9px] font-mono">
                                <span class="w-2 h-2 bg-upsilon-cyan"></span>
                                <span class="text-upsilon-steel uppercase">Compatible</span>
                            </div>
                            <div class="flex items-center gap-2 text-[9px] font-mono">
                                <span class="w-2 h-2 bg-upsilon-magenta"></span>
                                <span class="text-upsilon-steel uppercase">High Value</span>
                            </div>
                        </div>
                    </div>

                    <ShopGrid 
                        :items="items" 
                        :inventory="inventory"
                        :user-credits="userCredits"
                        :loading="loading"
                        @purchase="openConfirm"
                    />
                </div>
            </div>
        </div>

        <PurchaseConfirmModal 
            :show="showConfirm"
            :item="selectedItem"
            @close="showConfirm = false"
            @confirm="handlePurchase"
        />
    </TacticalLayout>
</template>

