<!-- @spec-link [[ui_dashboard]] -->
<script setup>
import { Head, router } from '@inertiajs/vue3';
import { getAuthUser } from '@/services/auth';
import { ref, onMounted } from 'vue';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import CharacterRoster from '@/Components/CharacterRoster.vue';

const user = ref(null);

onMounted(() => {
    user.value = getAuthUser();
    if (!user.value) {
        router.visit('/login');
    }
});

const globalStats = ref({
    waiting: '--',
    active: '--',
    lastUpdate: 'Manual Sync Required'
});

const playerStats = ref({
    ratio: '0.0',
    wins: 0,
    losses: 0
});

onMounted(() => {
    if (user.value) {
        playerStats.value = {
            ratio: user.value.ratio || '0.0',
            wins: user.value.total_wins || 0,
            losses: user.value.total_losses || 0
        };
    }
});
</script>

<template>
    <Head title="Tactical Command" />

    <TacticalLayout v-if="user" :user="user" :lastUpdate="globalStats.lastUpdate">
        <!-- Main Dashboard Grid -->
        <div class="relative z-10 flex-1 grid grid-cols-1 lg:grid-cols-12 gap-6 p-6 max-w-[1600px] mx-auto w-full">
            
            <!-- Left Column: Roster -->
            <aside class="lg:col-span-3">
                <CharacterRoster :user="user" />
            </aside>

            <!-- Center Column: Global Action -->
            <main class="lg:col-span-6 space-y-6">
                <!-- Global Match Stats Panel -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-black/50 border border-upsilon-steel/20 text-center">
                        <div class="text-3xl font-scifi text-upsilon-lime">{{ globalStats.waiting }}</div>
                        <div class="text-[8px] font-mono text-upsilon-steel uppercase tracking-tighter">Waiting Players</div>
                    </div>
                    <div class="p-4 bg-black/50 border border-upsilon-steel/20 text-center">
                        <div class="text-3xl font-scifi text-upsilon-cyan">{{ globalStats.active }}</div>
                        <div class="text-[8px] font-mono text-upsilon-steel uppercase tracking-tighter">Active Matches</div>
                    </div>
                </div>

                <!-- Action Hub -->
                <div class="p-6 bg-upsilon-gunmetal/30 border border-upsilon-cyan/30 backdrop-blur-md relative">
                    <div class="absolute -top-px -left-px w-6 h-6 border-t-2 border-l-2 border-upsilon-cyan"></div>
                    <div class="absolute -bottom-px -right-px w-6 h-6 border-b-2 border-r-2 border-upsilon-cyan"></div>

                    <h2 class="font-scifi text-[11px] text-upsilon-cyan uppercase tracking-[0.3em] mb-6 text-center">Initiate Engagement</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button class="group p-5 bg-black/60 border border-upsilon-lime/40 hover:border-upsilon-lime hover:bg-upsilon-lime/5 transition-all text-left relative overflow-hidden">
                            <span class="block font-scifi text-lg text-white mb-1 uppercase tracking-tighter">Solo / PVE</span>
                            <span class="block font-mono text-[9px] text-upsilon-steel uppercase group-hover:text-upsilon-lime/70">Sector Exploration & AI Cleanup</span>
                        </button>
                        
                        <button class="group p-5 bg-black/60 border border-upsilon-magenta/40 hover:border-upsilon-magenta hover:bg-upsilon-magenta/5 transition-all text-left relative overflow-hidden">
                            <span class="block font-scifi text-lg text-white mb-1 uppercase tracking-tighter">PVP / Ranked</span>
                            <span class="block font-mono text-[9px] text-upsilon-steel uppercase group-hover:text-upsilon-magenta/70">1v1 High Stakes Territory Claim</span>
                        </button>

                        <button class="group p-5 bg-black/60 border border-upsilon-lime/40 hover:border-upsilon-lime hover:bg-upsilon-lime/5 transition-all text-left relative overflow-hidden">
                            <span class="block font-scifi text-lg text-white mb-1 uppercase tracking-tighter">Co-op / PVE</span>
                            <span class="block font-mono text-[9px] text-upsilon-steel uppercase group-hover:text-upsilon-lime/70">2 Players vs AI Overdrive</span>
                        </button>

                        <button class="group p-5 bg-black/60 border border-upsilon-magenta/40 hover:border-upsilon-magenta hover:bg-upsilon-magenta/5 transition-all text-left relative overflow-hidden">
                            <span class="block font-scifi text-lg text-white mb-1 uppercase tracking-tighter">Duo / PVP</span>
                            <span class="block font-mono text-[9px] text-upsilon-steel uppercase group-hover:text-upsilon-magenta/70">2v2 Cooperative Combat Operations</span>
                        </button>
                    </div>
                </div>

                <div class="text-center">
                    <p class="text-[10px] font-mono text-upsilon-steel uppercase tracking-[0.4em] animate-pulse">Standing by for command input...</p>
                </div>
            </main>

            <!-- Right Column: Profile & Combat Record -->
            <aside class="lg:col-span-3 space-y-6">
                <!-- Combat Performance -->
                <div class="p-5 bg-upsilon-gunmetal/20 border border-upsilon-steel/30 backdrop-blur-sm relative">
                    <h2 class="font-scifi text-[10px] text-upsilon-steel uppercase tracking-[0.2em] mb-4">Combat Performance</h2>
                    
                    <div class="text-center mb-4">
                        <div class="text-4xl font-scifi text-white">{{ playerStats.ratio }} <span class="text-[10px] text-upsilon-steel">W/L</span></div>
                        
                        <div class="w-full h-1 flex mt-4 border border-upsilon-steel/20 bg-black/40">
                            <div class="bg-upsilon-lime h-full shadow-[0_0_5px_theme('colors.upsilon.lime')]" :style="{ width: (parseFloat(playerStats.ratio) / 4 * 100) + '%' }"></div>
                            <div class="bg-upsilon-magenta h-full shadow-[0_0_5px_theme('colors.upsilon.magenta')]" :style="{ width: (100 - (parseFloat(playerStats.ratio) / 4 * 100)) + '%' }"></div>
                        </div>
                        
                        <div class="flex justify-between mt-2 font-mono text-[8px] text-upsilon-steel uppercase">
                            <span>Wins: {{ playerStats.wins }}</span>
                            <span>Losses: {{ playerStats.losses }}</span>
                        </div>
                    </div>
                </div>

                <!-- Profile Management -->
                <div class="p-5 bg-upsilon-gunmetal/20 border border-upsilon-steel/30 backdrop-blur-sm relative">
                    <h2 class="font-scifi text-[10px] text-upsilon-steel uppercase tracking-[0.2em] mb-4">Identity Management</h2>
                    
                    <div class="space-y-3">
                        <button class="w-full text-left p-3 border border-upsilon-steel/20 bg-black/30 font-mono text-[9px] text-upsilon-steel uppercase hover:border-upsilon-cyan hover:text-white transition-all">
                            Edit Identity Data
                        </button>
                        <button class="w-full text-left p-3 border border-upsilon-steel/20 bg-black/30 font-mono text-[9px] text-upsilon-steel uppercase hover:border-upsilon-cyan hover:text-white transition-all">
                            GDPR Archive Export
                        </button>
                        <button class="w-full text-left p-3 border border-upsilon-magenta/30 bg-black/30 font-mono text-[9px] text-upsilon-magenta uppercase hover:bg-upsilon-magenta/10 transition-all">
                            Termination Protocol
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </TacticalLayout>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
