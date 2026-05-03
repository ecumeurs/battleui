<!-- @spec-link [[ui_leaderboard]] -->
<!-- @spec-link [[ui_leaderboard_data_display]] -->
<!-- @spec-link [[ui_leaderboard_modes]] -->
<script setup>
import { ref, onMounted, watch } from 'vue';
import auth from '@/services/auth';

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
});

const modes = [
    { id: '1v1_PVP', name: 'Duel (1v1 PVP)' },
    { id: '2v2_PVP', name: 'Squad (2v2 PVP)' },
    { id: '1v1_PVE', name: 'Sector (1v1 PVE)' },
    { id: '2v2_PVE', name: 'Ops (2v2 PVE)' }
];

const currentMode = ref('1v1_PVP');
const search = ref('');
const results = ref([]);
const self = ref(null);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);
const message = ref('');

const fetchLeaderboard = async (page = 1) => {
    loading.value = true;
    message.value = '';
    try {
        const response = await auth.get(`/leaderboard`, {
            params: {
                mode: currentMode.value,
                page: page,
                search: search.value.trim()
            }
        });
        
        if (response && response.success) {
            results.value = response.data.results;
            self.value = response.data.self;
            meta.value = response.data.meta;
            message.value = response.message;
        }
    } catch (error) {
        console.error("Failed to fetch leaderboard", error);
        message.value = "COMMUNICATIONS JAMMED: NO SIGNATURE FOUND";
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchLeaderboard();
});

watch(currentMode, () => {
    fetchLeaderboard(1);
});

const performSearch = () => {
    fetchLeaderboard(1);
};

const changePage = (page) => {
    if (page >= 1 && page <= meta.value.last_page) {
        fetchLeaderboard(page);
    }
};
</script>

<template>
    <div class="mt-8 border-t border-upsilon-cyan/10 pt-8">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h3 class="font-scifi text-upsilon-cyan text-xs uppercase tracking-[0.4em]">Global Competitive Feed</h3>
            
            <div class="flex items-center gap-2">
                <select 
                    v-model="currentMode" 
                    class="bg-black border border-upsilon-cyan/30 text-upsilon-cyan font-mono text-[9px] px-2 py-1 uppercase outline-none focus:border-upsilon-cyan transition-colors"
                >
                    <option v-for="mode in modes" :key="mode.id" :value="mode.id">{{ mode.name }}</option>
                </select>
                
                <div class="relative">
                    <input 
                        v-model="search"
                        @keyup.enter="performSearch"
                        type="text" 
                        placeholder="SEARCH ACCOUNT..."
                        class="bg-black/40 border border-upsilon-cyan/20 text-upsilon-lime font-mono text-[9px] px-3 py-1 uppercase outline-none focus:border-upsilon-lime/50 w-32 md:w-48 placeholder:text-upsilon-cyan/30"
                    />
                </div>
            </div>
        </div>

        <div class="relative bg-black/20 border border-upsilon-steel/10 min-h-[300px] overflow-hidden">
            <!-- Loading Overlay -->
            <div v-if="loading" class="absolute inset-0 bg-black/60 z-20 flex items-center justify-center backdrop-blur-[1px]">
                <div class="flex flex-col items-center gap-2">
                    <div class="w-8 h-8 border-2 border-upsilon-cyan/30 border-t-upsilon-cyan animate-spin rounded-full"></div>
                    <span class="font-mono text-[8px] text-upsilon-cyan uppercase animate-pulse">Scanning Data Pools...</span>
                </div>
            </div>

            <!-- Table -->
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-upsilon-cyan/10 bg-upsilon-cyan/5">
                        <th class="py-2 px-4 font-scifi text-[8px] text-upsilon-cyan/60 uppercase tracking-widest">Rank</th>
                        <th class="py-2 px-4 font-scifi text-[8px] text-upsilon-cyan/60 uppercase tracking-widest">Combatant</th>
                        <th class="py-2 px-4 font-scifi text-[8px] text-upsilon-cyan/60 uppercase tracking-widest text-right">Wins</th>
                        <th class="py-2 px-4 font-scifi text-[8px] text-upsilon-cyan/60 uppercase tracking-widest text-right">Losses</th>
                        <th class="py-2 px-4 font-scifi text-[8px] text-upsilon-cyan/60 uppercase tracking-widest text-right">Score</th>
                    </tr>
                </thead>
                <tbody v-if="results.length > 0">
                    <tr 
                        v-for="row in results" 
                        :key="row.rank" 
                        class="border-b border-upsilon-steel/5 hover:bg-upsilon-cyan/5 transition-colors group"
                        :class="{ 'bg-upsilon-lime/5': row.is_self }"
                    >
                        <td class="py-2 px-4 font-mono text-[10px] text-white/50 mr-2" :class="{ 'text-upsilon-cyan font-bold': row.rank <= 3 }">
                            #{{ row.rank }}
                        </td>
                        <td class="py-2 px-4">
                            <div class="flex flex-col">
                                <span class="font-mono text-[11px] text-white uppercase group-hover:text-upsilon-cyan transition-colors">{{ row.account_name }}</span>
                                <span v-if="row.is_self" class="text-[7px] text-upsilon-lime font-bold uppercase tracking-tighter">Your Neural Signature</span>
                            </div>
                        </td>
                        <td class="py-2 px-4 font-mono text-[10px] text-upsilon-lime text-right">{{ row.wins }}</td>
                        <td class="py-2 px-4 font-mono text-[10px] text-upsilon-magenta text-right">{{ row.losses }}</td>
                        <td class="py-2 px-4 font-mono text-[10px] text-upsilon-cyan text-right font-bold">{{ row.score.toFixed(2) }}</td>
                    </tr>

                    <!-- Self Context Pinned (if not on current page) -->
                    <tr v-if="self && !results.some(r => r.is_self)" class="bg-upsilon-lime/10 border-t-2 border-upsilon-lime/30">
                         <td class="py-2 px-4 font-mono text-[10px] text-upsilon-lime font-bold">
                            #{{ self.rank }}
                        </td>
                        <td class="py-2 px-4">
                            <div class="flex flex-col">
                                <span class="font-mono text-[11px] text-white uppercase">{{ self.account_name }}</span>
                                <span class="text-[7px] text-upsilon-lime font-bold uppercase tracking-tighter">External Signal Detected (Pinned)</span>
                            </div>
                        </td>
                        <td class="py-2 px-4 font-mono text-[10px] text-upsilon-lime text-right">{{ self.wins }}</td>
                        <td class="py-2 px-4 font-mono text-[10px] text-upsilon-magenta text-right">{{ self.losses }}</td>
                        <td class="py-2 px-4 font-mono text-[10px] text-upsilon-cyan text-right font-bold">{{ self.score.toFixed(2) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Empty State / Message -->
            <div v-if="!loading && results.length === 0" class="flex flex-col items-center justify-center p-12 text-center space-y-4">
                <div class="text-upsilon-magenta font-mono text-[10px] uppercase tracking-[0.5em] animate-pulse">
                    {{ message || 'NO SIGNAL DETECTED' }}
                </div>
                <div class="w-12 h-px bg-upsilon-magenta/30"></div>
                <p class="text-[8px] font-mono text-white/40 uppercase max-w-[200px]">
                    Area scavenged. No registered combatants matched the current sector criteria.
                </p>
                <button 
                   v-if="search"
                   @click="search = ''; performSearch()" 
                   class="text-[8px] font-mono text-upsilon-cyan uppercase underline underline-offset-4 hover:text-white transition-colors"
                >
                    Reset Visual Sensors
                </button>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="meta.last_page > 1" class="flex justify-between items-center mt-4">
            <button 
                @click="changePage(meta.current_page - 1)" 
                :disabled="meta.current_page === 1"
                class="font-mono text-[9px] text-upsilon-cyan/50 hover:text-upsilon-cyan uppercase disabled:opacity-30 disabled:hover:text-upsilon-cyan/50 transition-colors"
            >
                [ PREV_SECTOR ]
            </button>
            <div class="font-mono text-[9px] text-upsilon-cyan uppercase tracking-widest">
                Sector {{ meta.current_page }} / {{ meta.last_page }}
            </div>
            <button 
                @click="changePage(meta.current_page + 1)" 
                :disabled="meta.current_page === meta.last_page"
                class="font-mono text-[9px] text-upsilon-cyan/50 hover:text-upsilon-cyan uppercase disabled:opacity-30 disabled:hover:text-upsilon-cyan/50 transition-colors"
            >
                [ NEXT_SECTOR ]
            </button>
        </div>
    </div>
</template>

<style scoped>
select {
    appearance: none;
    -webkit-appearance: none;
    padding-right: 1.5rem !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2300f2ff'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.2rem center;
    background-size: 0.8rem;
}
</style>
