<script setup>
/** @spec-link [[uc_admin_history_management]] */
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import axios from 'axios';

const props = defineProps({
    user: Object,
});

const matchHistory = ref([]);
const hasMore = ref(false);
const nextCursor = ref(null);
const searchQuery = ref('');
const isSearching = ref(false);
const isLoadingMore = ref(false);
const isPurging = ref(false);

const fetchData = async (cursor = null) => {
    if (cursor) isLoadingMore.value = true;
    else isSearching.value = true;

    try {
        const response = await axios.get('/api/v1/admin/history', {
            params: { 
                search: searchQuery.value,
                cursor: cursor
            }
        });
        
        if (cursor) {
            matchHistory.value = [...matchHistory.value, ...response.data.data.items];
        } else {
            matchHistory.value = response.data.data.items;
        }
        
        hasMore.value = response.data.data.has_more;
        nextCursor.value = response.data.data.next_cursor;
    } catch (error) {
        console.error('Failed to fetch match history:', error);
    } finally {
        isSearching.value = false;
        isLoadingMore.value = false;
    }
};

const handleSearch = () => fetchData();
const loadMore = () => fetchData(nextCursor.value);

const handlePurge = async () => {
    if (confirm('CRITICAL ACTION: This will permanently delete all match records older than 90 days. This cannot be undone. Proceed?')) {
        isPurging.value = true;
        try {
            const response = await axios.post('/api/v1/admin/history/purge');
            alert(`Purge successful. ${response.data.data.purged_count} records removed.`);
            fetchData(); // Refresh list
        } catch (error) {
            console.error('Purge failed:', error);
            alert('Purge operation failed. check system logs.');
        } finally {
            isPurging.value = false;
        }
    }
};

onMounted(() => fetchData());

const formatDate = (date) => date ? new Date(date).toLocaleString() : 'IN PROGRESS';
</script>

<template>
    <Head title="Match History Audit" />

    <TacticalLayout :user="user">
        <div class="relative z-10 flex-1 p-6 max-w-[1400px] mx-auto w-full space-y-8">
            <div class="flex items-end justify-between border-b border-upsilon-magenta/20 pb-4">
                <div>
                    <h1 class="text-3xl font-scifi text-upsilon-magenta uppercase tracking-tighter">
                        Match History Audit
                    </h1>
                    <p class="text-upsilon-magenta/60 font-mono text-[10px] uppercase tracking-widest mt-1">
                        System Logs // Tactical Record Maintenance
                    </p>
                </div>
                <div class="flex items-center gap-6">
                    <!-- Search Bar -->
                    <div class="relative">
                        <input 
                            v-model="searchQuery"
                            @keyup.enter="handleSearch"
                            type="text" 
                            placeholder="SEARCH BY MATCH ID / PLAYER..."
                            class="bg-black/60 border border-upsilon-magenta/30 px-4 py-2 text-[10px] font-mono text-upsilon-magenta placeholder:text-upsilon-magenta/30 focus:outline-none focus:border-upsilon-magenta w-64 uppercase tracking-widest"
                        />
                        <button 
                            @click="handleSearch"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-upsilon-magenta/60 hover:text-upsilon-magenta"
                        >
                            <svg v-if="!isSearching" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <svg v-else class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>

                    <button 
                        @click="handlePurge"
                        :disabled="isPurging"
                        class="px-4 py-2 bg-red-900/20 border border-red-500/30 text-red-500 font-mono text-[10px] uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all disabled:opacity-50"
                    >
                        {{ isPurging ? 'Purging...' : 'Purge Old History' }}
                    </button>
                </div>
            </div>

            <!-- Match Table -->
            <div class="bg-black/40 border border-upsilon-steel/20 overflow-hidden">
                <table class="w-full text-left font-mono text-xs">
                    <thead class="bg-upsilon-steel/10 text-upsilon-cyan uppercase tracking-widest text-[9px]">
                        <tr>
                            <th class="px-4 py-3 border-b border-upsilon-steel/20">Match ID</th>
                            <th class="px-4 py-3 border-b border-upsilon-steel/20">Mode</th>
                            <th class="px-4 py-3 border-b border-upsilon-steel/20">Participants</th>
                            <th class="px-4 py-3 border-b border-upsilon-steel/20">Started At</th>
                            <th class="px-4 py-3 border-b border-upsilon-steel/20">Concluded At</th>
                            <th class="px-4 py-3 border-b border-upsilon-steel/20 text-right">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-upsilon-steel/10">
                        <tr v-for="match in matchHistory" :key="match.id" 
                            class="hover:bg-upsilon-magenta/5 transition-colors group"
                        >
                            <td class="px-4 py-4 font-bold text-white text-[10px] uppercase">{{ match.id.substring(0, 8) }}...</td>
                            <td class="px-4 py-4 text-upsilon-steel uppercase">{{ match.game_mode }}</td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="p in match.participants" :key="p.id" 
                                          class="px-2 py-0.5 bg-upsilon-magenta/10 border border-upsilon-magenta/20 text-[9px] text-upsilon-magenta uppercase"
                                    >
                                        {{ p.player?.account_name || 'GUEST' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-upsilon-steel/60">{{ formatDate(match.started_at) }}</td>
                            <td class="px-4 py-4 text-upsilon-steel/60">{{ formatDate(match.concluded_at) }}</td>
                            <td class="px-4 py-4 text-right">
                                <span v-if="match.winner_team_id !== null" class="text-upsilon-lime uppercase font-bold">
                                    TEAM {{ match.winner_team_id }} VICTORY
                                </span>
                                <span v-else-if="match.concluded_at" class="text-upsilon-steel uppercase">
                                    DRAW / ABORTED
                                </span>
                                <span v-else class="text-upsilon-magenta animate-pulse uppercase">
                                    ACTIVE
                                </span>
                            </td>
                        </tr>
                        <tr v-if="matchHistory.length === 0 && !isSearching">
                            <td colspan="6" class="px-4 py-12 text-center text-upsilon-steel/40 uppercase tracking-widest text-[10px]">
                                No records found in the tactical history.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="hasMore" class="flex justify-center pt-4">
                <button 
                    @click="loadMore"
                    :disabled="isLoadingMore"
                    class="group relative px-8 py-3 bg-black/40 border border-upsilon-magenta/30 text-upsilon-magenta font-mono text-[10px] uppercase tracking-[0.3em] overflow-hidden hover:border-upsilon-magenta transition-all active:scale-95 disabled:opacity-50"
                >
                    <span v-if="!isLoadingMore">Access Older Logs</span>
                    <span v-else class="flex items-center gap-2">
                        <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Retrieving Archive...
                    </span>
                    <div class="absolute bottom-0 left-0 h-0.5 w-0 bg-upsilon-magenta transition-all group-hover:w-full"></div>
                </button>
            </div>

            <div class="flex items-center gap-2 text-[8px] font-mono text-upsilon-steel uppercase tracking-widest">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-upsilon-magenta" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Caution: History purge is permanent. Ensure all necessary audits are completed before maintenance.
            </div>
        </div>
    </TacticalLayout>
</template>
