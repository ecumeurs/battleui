<script setup>
/** @spec-link [[requirement_customer_user_id_privacy]] */
import { Link } from '@inertiajs/vue3';
import { logout } from '@/services/auth';
import { router } from '@inertiajs/vue3';
import { getTacticalId } from '@/services/tactical_id';
import { onMounted, ref } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        required: false,
        default: null
    }
});

const tacticalId = ref('');

onMounted(() => {
    tacticalId.value = getTacticalId();
});

const handleLogout = async () => {
    await logout();
    router.visit('/');
};
</script>

<template>
    <header class="relative z-10 w-full px-8 py-4 flex justify-between items-center border-b border-upsilon-steel/30 bg-black/40 backdrop-blur-md">
        <div class="flex items-center gap-6">
            <Link href="/" class="text-2xl font-scifi font-bold text-white uppercase tracking-tighter hover:text-upsilon-cyan transition-colors">
                UPSILON<span class="text-upsilon-cyan italic">BATTLE</span>
            </Link>
            <div class="h-8 w-px bg-upsilon-steel/30 hidden md:block"></div>
            <div class="hidden md:flex flex-col">
                <span class="text-upsilon-cyan font-mono text-[10px] uppercase tracking-widest truncate w-40">ENTITY: {{ user ? user.account_name : 'GUEST' }}</span>
                <span class="text-upsilon-lime font-mono text-[8px] uppercase tracking-widest">ROLE: {{ user ? user.role : 'GUEST' }}</span>
            </div>
            <div v-if="user && user.role === 'Admin'" class="h-8 w-px bg-upsilon-steel/30 hidden md:block"></div>
            <Link 
                v-if="user && user.role === 'Admin'"
                href="/admin/dashboard" 
                class="hidden md:flex flex-col group"
            >
                <span class="text-upsilon-magenta font-mono text-[10px] uppercase tracking-widest group-hover:text-white transition-colors">Admin Terminal</span>
                <span class="text-upsilon-magenta/60 font-mono text-[8px] uppercase tracking-widest group-hover:text-upsilon-magenta/100 transition-colors">System Management</span>
            </Link>
        </div>

        <button 
            v-if="user"
            @click="handleLogout"
            class="px-5 py-2 border border-upsilon-magenta text-upsilon-magenta font-mono text-[10px] uppercase tracking-widest hover:bg-upsilon-magenta hover:text-white transition-all duration-300 shadow-glow-magenta/20"
        >
            Terminate Session
        </button>
        <Link 
            v-else
            href="/login"
            class="px-5 py-2 border border-upsilon-cyan text-upsilon-cyan font-mono text-[10px] uppercase tracking-widest hover:bg-upsilon-cyan hover:text-white transition-all duration-300 shadow-glow-cyan/20"
        >
            Authorize Terminal
        </Link>
    </header>
</template>
