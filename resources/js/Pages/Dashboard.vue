<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { logout, getAuthUser } from '@/services/auth';
import { ref, onMounted } from 'vue';

const user = ref(null);

onMounted(() => {
    user.value = getAuthUser();
    if (!user.value) {
        router.visit('/login');
    }
});

const handleLogout = async () => {
    await logout();
    router.visit('/');
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="relative min-h-screen bg-upsilon-void overflow-hidden flex flex-col font-sans selection:bg-upsilon-cyan selection:text-upsilon-void">
        <!-- Background Layer -->
        <div class="absolute inset-0 bg-hero-bg bg-cover bg-center opacity-20"></div>
        <div class="absolute inset-0 bg-panel-texture bg-repeat opacity-10"></div>

        <!-- Top Navigation -->
        <header class="relative z-10 w-full px-8 py-6 flex justify-between items-center border-b border-upsilon-steel/30 bg-black/40 backdrop-blur-sm">
            <div class="flex items-center gap-4">
                <Link href="/" class="text-2xl font-scifi font-bold text-white uppercase tracking-tighter">
                    UPSILON<span class="text-upsilon-cyan italic">BATTLE</span>
                </Link>
                <div class="h-8 w-px bg-upsilon-steel/30 hidden md:block"></div>
                <div v-if="user" class="hidden md:flex flex-col">
                    <span class="text-upsilon-cyan font-mono text-[10px] uppercase tracking-widest line-clamp-1 truncate w-32">ENTITY: {{ user.account_name }}</span>
                    <span class="text-upsilon-steel font-mono text-[8px] uppercase tracking-widest line-clamp-1 truncate w-32">ID: {{ user.id }}</span>
                </div>
            </div>

            <button 
                @click="handleLogout"
                class="px-4 py-2 border border-upsilon-magenta text-upsilon-magenta font-mono text-xs uppercase tracking-widest hover:bg-upsilon-magenta hover:text-white transition-colors"
            >
                Terminate Session
            </button>
        </header>

        <!-- Main Content -->
        <main class="relative z-10 flex-1 flex flex-col items-center justify-center p-6 text-center">
            <div class="mb-4">
                <div class="inline-block px-3 py-1 bg-upsilon-lime/20 border border-upsilon-lime text-upsilon-lime font-mono text-[10px] uppercase tracking-widest mb-4">
                    LINK_ESTABLISHED
                </div>
            </div>

            <h1 class="text-5xl md:text-7xl font-scifi font-bold text-white mb-6 uppercase tracking-tight">
                UNDER <span class="text-upsilon-cyan animate-pulse">CONSTRUCTION</span>
            </h1>

            <div class="w-full max-w-2xl p-8 border border-upsilon-steel/30 bg-upsilon-gunmetal/20 backdrop-blur-sm relative mb-12">
                <!-- Decorative Corners -->
                <div class="absolute -top-1 -left-1 w-4 h-4 border-t-2 border-l-2 border-upsilon-cyan"></div>
                <div class="absolute -bottom-1 -right-1 w-4 h-4 border-b-2 border-r-2 border-upsilon-cyan"></div>

                <p class="text-upsilon-steel font-mono text-sm uppercase leading-relaxed mb-6">
                    The tactical bridge is currently being calibrated. Matchmaking, Character rosters, and Arena access are coming online in the next cycle.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">
                    <div class="p-4 bg-black/40 border border-upsilon-steel/20">
                        <div class="text-upsilon-lime font-mono text-[10px] uppercase mb-1">Status</div>
                        <div class="text-white font-mono text-xs uppercase">Core Engine: OK</div>
                    </div>
                    <div class="p-4 bg-black/40 border border-upsilon-steel/20">
                        <div class="text-upsilon-magenta font-mono text-[10px] uppercase mb-1">Queue</div>
                        <div class="text-white font-mono text-xs uppercase">Wait: N/A</div>
                    </div>
                </div>
            </div>

            <p class="text-upsilon-steel font-mono text-[10px] uppercase tracking-widest">
                Please stand by for further instructions from Upsilon Command.
            </p>
        </main>

        <!-- Footer Decor -->
        <div class="absolute bottom-4 left-8 right-8 flex justify-between pointer-events-none opacity-40">
            <div class="font-mono text-[8px] text-upsilon-cyan uppercase">DATA_STREAM: [100.22.44.11]</div>
            <div class="font-mono text-[8px] text-upsilon-cyan uppercase">SIGNAL_STRENGTH: 98%</div>
        </div>
    </div>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
