<script setup>
/** @spec-link [[uc_admin_user_management]] */
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import axios from 'axios';

const props = defineProps({
    user: Object,
    users: Array,
    initialHasMore: Boolean,
    initialNextCursor: String,
});

const userList = ref([...props.users]);
const hasMore = ref(props.initialHasMore);
const nextCursor = ref(props.initialNextCursor);
const searchQuery = ref('');
const isSearching = ref(false);
const isLoadingMore = ref(false);

const handleSearch = async () => {
    isSearching.value = true;
    try {
        const response = await axios.get('/api/v1/admin/users', {
            params: { search: searchQuery.value }
        });
        userList.value = response.data.data.items;
        hasMore.value = response.data.data.has_more;
        nextCursor.value = response.data.data.next_cursor;
    } catch (error) {
        console.error('Search failed:', error);
    } finally {
        isSearching.value = false;
    }
};

const loadMore = async () => {
    if (isLoadingMore.value || !hasMore.value) return;
    
    isLoadingMore.value = true;
    try {
        const response = await axios.get('/api/v1/admin/users', {
            params: { 
                search: searchQuery.value,
                cursor: nextCursor.value
            }
        });
        userList.value = [...userList.value, ...response.data.data.items];
        hasMore.value = response.data.data.has_more;
        nextCursor.value = response.data.data.next_cursor;
    } catch (error) {
        console.error('Load more failed:', error);
    } finally {
        isLoadingMore.value = false;
    }
};

const handleAnonymize = (account_name) => {
    if (confirm('CAUTION: This will anonymize address and birth date data. This action is IRREVERSIBLE. Proceed?')) {
        router.post(route('admin.users.anonymize', account_name), {}, {
            onSuccess: () => {
                // Refresh local state if needed or let Inertia handle it
                // router.reload preserves state by default
            }
        });
    }
};

const handleDelete = (account_name) => {
    if (confirm('SOFT DELETE: The account will be deactivated but data remains for auditing. Proceed?')) {
        router.delete(route('admin.users.destroy', account_name));
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString();
</script>

<template>
    <Head title="User Management" />

    <TacticalLayout :user="user">
        <div class="relative z-10 flex-1 p-6 max-w-[1400px] mx-auto w-full space-y-8">
            <div class="flex items-end justify-between border-b border-upsilon-magenta/20 pb-4">
                <div>
                    <h1 class="text-3xl font-scifi text-upsilon-magenta uppercase tracking-tighter">
                        User Management
                    </h1>
                    <p class="text-upsilon-magenta/60 font-mono text-ui-sm uppercase tracking-widest mt-1">
                        Registry Audit // GDPR Compliance Terminal
                    </p>
                </div>
                <div class="flex items-center gap-6">
                    <!-- Search Bar -->
                    <div class="relative">
                        <input 
                            v-model="searchQuery"
                            @keyup.enter="handleSearch"
                            type="text" 
                            placeholder="SEARCH BY HANDLE / EMAIL..."
                            class="bg-black/60 border border-upsilon-magenta/30 px-4 py-2 text-ui-sm font-mono text-upsilon-magenta placeholder:text-upsilon-magenta/30 focus:outline-none focus:border-upsilon-magenta w-64 uppercase tracking-widest"
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

                    <div class="px-3 py-1 bg-upsilon-magenta/10 border border-upsilon-magenta/20 text-ui-sm font-mono text-upsilon-magenta uppercase">
                        Entities: {{ userList.length }}{{ hasMore ? '+' : '' }}
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-black/40 border border-upsilon-steel/20 overflow-hidden">
                <table class="w-full text-left font-mono text-ui-sm">
                    <thead class="bg-upsilon-steel/10 text-upsilon-lime uppercase tracking-widest text-ui-xs">
                        <tr>
                            <th class="px-4 py-3 border-b border-upsilon-steel/20">Handle</th>
                            <th class="px-4 py-3 border-b border-upsilon-steel/20">Email</th>
                            <th class="px-4 py-3 border-b border-upsilon-steel/20">Role</th>
                            <th class="px-4 py-3 border-b border-upsilon-steel/20">Joined</th>
                            <th class="px-4 py-3 border-b border-upsilon-steel/20">Status</th>
                            <th class="px-4 py-3 border-b border-upsilon-steel/20 text-right">Operations</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-upsilon-steel/10">
                        <tr v-for="target in userList" :key="target.id" 
                            :class="target.deleted_at ? 'opacity-50 grayscale bg-red-900/10' : 'hover:bg-upsilon-cyan/5'"
                            class="transition-colors group"
                        >
                            <td class="px-4 py-4 font-bold text-white uppercase">{{ target.account_name }}</td>
                            <td class="px-4 py-4 text-upsilon-cyan/70">{{ target.email }}</td>
                            <td class="px-4 py-4">
                                <span :class="target.role === 'Admin' ? 'text-upsilon-magenta' : 'text-upsilon-lime'" class="uppercase font-bold">
                                    {{ target.role }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-upsilon-steel">{{ formatDate(target.created_at) }}</td>
                            <td class="px-4 py-4 uppercase">
                                <span v-if="target.deleted_at" class="text-upsilon-magenta">Terminated</span>
                                <span v-else class="text-upsilon-lime">Active</span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="flex justify-end gap-2" v-if="!target.deleted_at && target.id !== user.id">
                                    <button 
                                        @click="handleAnonymize(target.account_name)"
                                        class="px-3 py-1 border border-upsilon-magenta/40 text-upsilon-magenta uppercase text-ui-xs hover:bg-upsilon-magenta hover:text-white transition-colors"
                                        title="GDPR Right to be Forgotten"
                                    >
                                        Anonymize
                                    </button>
                                    <button 
                                        @click="handleDelete(target.account_name)"
                                        class="px-3 py-1 border border-upsilon-magenta/40 text-upsilon-magenta uppercase text-ui-xs hover:bg-upsilon-magenta hover:text-white transition-colors"
                                    >
                                        Delete
                                    </button>
                                </div>
                                <span v-else-if="target.id === user.id" class="text-ui-xs text-upsilon-steel italic uppercase px-3 py-1">Self (Active)</span>
                                <span v-else class="text-ui-xs text-upsilon-steel uppercase px-3 py-1">—</span>
                            </td>
                        </tr>
                        <tr v-if="userList.length === 0 && !isSearching">
                            <td colspan="6" class="px-4 py-12 text-center text-upsilon-steel/40 uppercase tracking-widest text-ui-sm">
                                No entities found matching current search criteria.
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
                    class="group relative px-8 py-3 bg-black/40 border border-upsilon-magenta/30 text-upsilon-magenta font-mono text-ui-sm uppercase tracking-[0.3em] overflow-hidden hover:border-upsilon-magenta transition-all active:scale-95 disabled:opacity-50"
                >
                    <span v-if="!isLoadingMore">Load More Entities</span>
                    <span v-else class="flex items-center gap-2">
                        <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Accessing Database...
                    </span>
                    <div class="absolute bottom-0 left-0 h-0.5 w-0 bg-upsilon-magenta transition-all group-hover:w-full"></div>
                </button>
            </div>

            <div class="flex items-center gap-2 text-ui-xs font-mono text-upsilon-steel uppercase tracking-widest">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-upsilon-magenta" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Caution: Logged actions are audit-tracked. Manual pagination active to maintain terminal performance.
            </div>
        </div>
    </TacticalLayout>
</template>
