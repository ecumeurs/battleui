<script setup>
/** @spec-link [[uc_admin_user_management]] */
import { Head, router } from '@inertiajs/vue3';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';

const props = defineProps({
    user: Object,
    users: Array,
});

const handleAnonymize = (id) => {
    if (confirm('CAUTION: This will anonymize address and birth date data. This action is IRREVERSIBLE. Proceed?')) {
        router.post(route('admin.users.anonymize', id));
    }
};

const handleDelete = (id) => {
    if (confirm('SOFT DELETE: The account will be deactivated but data remains for auditing. Proceed?')) {
        router.delete(route('admin.users.destroy', id));
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
                    <p class="text-upsilon-magenta/60 font-mono text-[10px] uppercase tracking-widest mt-1">
                        Registry Audit // GDPR Compliance Terminal
                    </p>
                </div>
                <div class="flex gap-4">
                    <div class="px-3 py-1 bg-upsilon-magenta/10 border border-upsilon-magenta/20 text-[10px] font-mono text-upsilon-magenta uppercase">
                        Active Entities: {{ users.filter(u => !u.deleted_at).length }}
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-black/40 border border-upsilon-steel/20 overflow-hidden">
                <table class="w-full text-left font-mono text-xs">
                    <thead class="bg-upsilon-steel/10 text-upsilon-lime uppercase tracking-widest text-[9px]">
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
                        <tr v-for="target in users" :key="target.id" 
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
                                        @click="handleAnonymize(target.id)"
                                        class="px-3 py-1 border border-upsilon-magenta/40 text-upsilon-magenta uppercase text-[9px] hover:bg-upsilon-magenta hover:text-white transition-colors"
                                        title="GDPR Right to be Forgotten"
                                    >
                                        Anonymize
                                    </button>
                                    <button 
                                        @click="handleDelete(target.id)"
                                        class="px-3 py-1 border border-upsilon-magenta/40 text-upsilon-magenta uppercase text-[9px] hover:bg-upsilon-magenta hover:text-white transition-colors"
                                    >
                                        Delete
                                    </button>
                                </div>
                                <span v-else-if="target.id === user.id" class="text-[9px] text-upsilon-steel italic uppercase px-3 py-1">Self (Active)</span>
                                <span v-else class="text-[9px] text-upsilon-steel uppercase px-3 py-1">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center gap-2 text-[8px] font-mono text-upsilon-steel uppercase tracking-widest">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-upsilon-magenta" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Caution: Logged actions are audit-tracked. Anonymization bypasses recovery protocols.
            </div>
        </div>
    </TacticalLayout>
</template>
