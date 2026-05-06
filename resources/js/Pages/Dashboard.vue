<!-- @spec-link [[ui_dashboard]] -->
<script setup>
import { Head, router } from '@inertiajs/vue3';
import { getAuthUser } from '@/services/auth';
import { ref, onMounted, onUnmounted } from 'vue';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import CharacterRoster from '@/Components/Dashboard/CharacterRoster.vue';
import IdentitySection from '@/Components/Dashboard/IdentitySection.vue';
import EngagementHub from '@/Components/Dashboard/EngagementHub.vue';
import LeaderboardComponent from '@/Components/Dashboard/LeaderboardComponent.vue';
import NeonShopButton from '@/Components/Shop/NeonShopButton.vue';
import TacticalPanel from '@/Components/Dashboard/TacticalPanel.vue';
import { useDashboardState } from '@/Composables/useDashboardState';
import { usePrivateChannel } from '@/Composables/usePrivateChannel';
import auth from '@/services/auth';

const { user, loading, initialized, init, updateUser, reset } = useDashboardState();
usePrivateChannel(user);

// Panel state
const panelMode        = ref(null);   // null | 'character' | 'shop' | 'inventory' | 'identity'
const panelCharacterId = ref(null);

function openPanel(mode, characterId = null) {
    panelMode.value        = mode;
    panelCharacterId.value = characterId;
}

function closePanel() {
    panelMode.value        = null;
    panelCharacterId.value = null;
}

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

        if (waitingData) globalStats.value.waiting = waitingData.waiting_count;
        if (activeData)  globalStats.value.active  = activeData.active_count;

        globalStats.value.lastUpdate = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    } catch {
        globalStats.value.lastUpdate = 'Sync Error — Retrying...';
    }
};

let statsInterval = null;

onMounted(async () => {
    const authUser = getAuthUser();
    if (!authUser) {
        router.visit('/login');
        return;
    }

    await init(authUser);

    playerStats.value = {
        ratio:  authUser.ratio       || '0.0',
        wins:   authUser.total_wins  || 0,
        losses: authUser.total_losses || 0,
    };

    fetchGlobalStats();
    statsInterval = setInterval(fetchGlobalStats, 60000);
});

onUnmounted(() => {
    if (statsInterval) clearInterval(statsInterval);
    reset();
});
</script>

<template>
    <Head title="Tactical Command" />

    <TacticalLayout v-if="user" :user="user" :lastUpdate="globalStats.lastUpdate">
        <!-- Main Dashboard Grid -->
        <div class="relative z-10 flex-1 grid grid-cols-1 lg:grid-cols-12 gap-6 p-6 max-w-[1600px] mx-auto w-full">

            <!-- Left Column: Roster -->
            <aside class="lg:col-span-3">
                <CharacterRoster
                    :user="user"
                    :selected-character-id="panelMode === 'character' ? panelCharacterId : null"
                    @character-select="openPanel('character', $event)"
                />
            </aside>

            <!-- Center Column: Global Action -->
            <main class="lg:col-span-6 space-y-6">
                <!-- Global Match Stats Panel -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-black/50 border border-upsilon-steel/20 text-center">
                        <div class="text-3xl font-scifi text-upsilon-lime">{{ globalStats.waiting }}</div>
                        <div class="text-ui-xs font-mono text-upsilon-lime uppercase tracking-tighter">Waiting Players</div>
                    </div>
                    <div class="p-4 bg-black/50 border border-upsilon-steel/20 text-center">
                        <div class="text-3xl font-scifi text-upsilon-cyan">{{ globalStats.active }}</div>
                        <div class="text-ui-xs font-mono text-upsilon-lime uppercase tracking-tighter">Active Matches</div>
                    </div>
                </div>

                <!-- Action Hub -->
                <EngagementHub :user="user" />

                <!-- Competitive Rank Feed -->
                <LeaderboardComponent :user="user" />
            </main>

            <!-- Right Column: Identity, Shop & Inventory -->
            <div class="lg:col-span-3 space-y-4">
                <IdentitySection
                    :user="user"
                    :playerStats="playerStats"
                    @open-identity="openPanel('identity')"
                />

                <!-- Inventory access -->
                <button
                    @click="openPanel('inventory')"
                    class="w-full px-4 py-3 border border-upsilon-cyan/40 bg-black/40 text-upsilon-cyan font-mono text-ui-sm uppercase tracking-[0.3em] hover:bg-upsilon-cyan/10 hover:border-upsilon-cyan flex items-center justify-between group"
                    style="transition: background-color 150ms linear, border-color 150ms linear;"
                >
                    <span>◈ Inventory Archive</span>
                    <span class="text-upsilon-cyan/50 group-hover:text-upsilon-cyan" style="transition: color 150ms linear;">›</span>
                </button>

                <!-- Neon shop CTA -->
                <NeonShopButton @click="openPanel('shop')" />
            </div>
        </div>

        <!-- Universal TacticalPanel -->
        <TacticalPanel
            :mode="panelMode"
            :character-id="panelCharacterId"
            :user="user"
            @close="closePanel"
        />
    </TacticalLayout>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
.animate-pulse {
    animation: pulse 2s linear infinite;
}
</style>
