<!-- @spec-link [[ui_dashboard]] -->
<script setup>
import { Head, router } from '@inertiajs/vue3';
import { getAuthUser } from '@/services/auth';
import { ref, onMounted } from 'vue';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import CharacterRoster from '@/Components/CharacterRoster.vue';
import IdentitySection from '@/Components/IdentitySection.vue';
import EngagementHub from '@/Components/EngagementHub.vue';
import LeaderboardComponent from '@/Components/LeaderboardComponent.vue';

import auth from '@/services/auth';

const user = ref(null);

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

const fetchGlobalStats = async () => {
    try {
        const [waitingData, activeData] = await Promise.all([
            auth.get('/match/stats/waiting'),
            auth.get('/match/stats/active')
        ]);

        if (waitingData) {
            globalStats.value.waiting = waitingData.waiting_count;
        }
        
        if (activeData) {
            globalStats.value.active = activeData.active_count;
        }

        globalStats.value.lastUpdate = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    } catch (error) {
        console.error("Failed to fetch match statistics", error);
        globalStats.value.lastUpdate = "Sync Error - Retrying...";
    }
};

let statsInterval = null;

onMounted(() => {
    user.value = getAuthUser();
    if (!user.value) {
        router.visit('/login');
        return;
    }

    playerStats.value = {
        ratio: user.value.ratio || '0.0',
        wins: user.value.total_wins || 0,
        losses: user.value.total_losses || 0
    };

    fetchGlobalStats();
    statsInterval = setInterval(fetchGlobalStats, 60000);
});

import { onUnmounted } from 'vue';
onUnmounted(() => {
    if (statsInterval) clearInterval(statsInterval);
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
                        <div class="text-[8px] font-mono text-upsilon-lime uppercase tracking-tighter">Waiting Players</div>
                    </div>
                    <div class="p-4 bg-black/50 border border-upsilon-steel/20 text-center">
                        <div class="text-3xl font-scifi text-upsilon-cyan">{{ globalStats.active }}</div>
                        <div class="text-[8px] font-mono text-upsilon-lime uppercase tracking-tighter">Active Matches</div>
                    </div>
                </div>

                <!-- Action Hub -->
                <EngagementHub :user="user" />

                <!-- Competitive Rank Feed -->
                <LeaderboardComponent :user="user" />
            </main>

            <!-- Right Column: Identity & Combat Record -->
            <IdentitySection 
                class="lg:col-span-3" 
                :user="user" 
                :playerStats="playerStats" 
            />
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
