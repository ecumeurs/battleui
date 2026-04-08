<script setup>
import { Link } from '@inertiajs/vue3';
import { logout } from '@/services/auth';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
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
                <span class="text-upsilon-cyan font-mono text-[10px] uppercase tracking-widest truncate w-40">ENTITY: {{ user.account_name }}</span>
                <span class="text-upsilon-steel font-mono text-[8px] uppercase tracking-widest">ID: {{ user.id }}</span>
            </div>
        </div>

        <button 
            @click="handleLogout"
            class="px-5 py-2 border border-upsilon-magenta text-upsilon-magenta font-mono text-[10px] uppercase tracking-widest hover:bg-upsilon-magenta hover:text-white transition-all duration-300 shadow-glow-magenta/20"
        >
            Terminate Session
        </button>
    </header>
</template>
