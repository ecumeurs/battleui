<!-- @spec-link [[ui_dashboard_matchmaking]] -->
<!-- @spec-link [[rule_matchmaking_single_queue]] -->
<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import auth from '@/services/auth';

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
});

const status = ref('idle'); // idle, queued, matched
const matchmakingData = ref(null);
const loading = ref(false);
const error = ref(null);

const fetchStatus = async () => {
    try {
        const response = await auth.get('/matchmaking/status');
        if (response) {
            status.value = response.status;
            matchmakingData.value = response;
            
            if (status.value === 'matched' && response.match_id) {
                redirectToArena(response.match_id);
            }
        }
    } catch (err) {
        console.error("Failed to fetch matchmaking status", err);
    }
};

const joinQueue = async (mode) => {
    if (loading.value || status.value !== 'idle') return;
    
    loading.value = true;
    error.value = null;
    
    try {
        const response = await auth.post('/matchmaking/join', { game_mode: mode });
        if (response) {
            status.value = response.status;
            matchmakingData.value = response;
            
            if (status.value === 'matched' && response.match_id) {
                redirectToArena(response.match_id);
            }
        }
    } catch (err) {
        error.value = err.response?.data?.message || "Deployment failed. Resource conflict detected.";
        setTimeout(() => { error.value = null; }, 5000);
    } finally {
        loading.value = false;
    }
};

const leaveQueue = async () => {
    if (loading.value) return;
    
    loading.value = true;
    try {
        await auth.delete('/matchmaking/leave');
        status.value = 'idle';
        matchmakingData.value = null;
    } catch (err) {
        console.error("Failed to leave queue", err);
    } finally {
        loading.value = false;
    }
};

const redirectToArena = (matchId) => {
    router.visit(`/battlearena?match_id=${matchId}`);
};

let statusInterval = null;

onMounted(() => {
    fetchStatus();
    // Poll as fallback for websocket
    statusInterval = setInterval(fetchStatus, 5000);

    // WebSocket listener
    if (window.Echo) {
        window.Echo.private(`user.${props.user.id}`)
            .listen('.match.found', (e) => {
                console.log("Match Found via WebSocket!", e);
                status.value = 'matched';
                redirectToArena(e.match_id);
            });
    }
});

onUnmounted(() => {
    if (statusInterval) clearInterval(statusInterval);
    if (window.Echo) {
        window.Echo.leave(`user.${props.user.id}`);
    }
});

const modes = [
    { id: '1v1_PVE', name: 'Solo / PVE', desc: 'Sector Exploration & AI Cleanup', color: 'upsilon-lime' },
    { id: '1v1_PVP', name: 'PVP / Ranked', desc: '1v1 High Stakes Territory Claim', color: 'upsilon-magenta' },
    { id: '2v2_PVE', name: 'Co-op / PVE', desc: '2 Players vs AI Overdrive', color: 'upsilon-lime' },
    { id: '2v2_PVP', name: 'Duo / PVP', desc: '2v2 Cooperative Combat Operations', color: 'upsilon-magenta' }
];

</script>

<template>
    <div class="p-6 bg-upsilon-gunmetal/30 border border-upsilon-cyan/30 backdrop-blur-md relative overflow-hidden">
        <!-- Structural Accents -->
        <div class="absolute -top-px -left-px w-6 h-6 border-t-2 border-l-2 border-upsilon-cyan"></div>
        <div class="absolute -bottom-px -right-px w-6 h-6 border-b-2 border-r-2 border-upsilon-cyan"></div>
        <div class="absolute top-0 right-0 w-32 h-32 bg-upsilon-cyan/5 blur-3xl rounded-full translate-x-16 -translate-y-16"></div>

        <div class="relative z-10">
            <h2 class="font-scifi text-[11px] text-upsilon-cyan uppercase tracking-[0.3em] mb-6 text-center">
                <span v-if="status === 'idle'">Initiate Engagement</span>
                <span v-else-if="status === 'queued'" class="animate-pulse">Strategic Alignment in Progress</span>
                <span v-else>Link Established</span>
            </h2>
            
            <div v-if="error" class="mb-4 p-3 bg-red-900/40 border border-red-500/50 text-red-200 text-[10px] font-mono uppercase tracking-widest text-center">
                {{ error }}
            </div>

            <!-- Idle State: Mode Selection -->
            <div v-if="status === 'idle'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <button 
                    v-for="mode in modes" 
                    :key="mode.id"
                    @click="joinQueue(mode.id)"
                    :disabled="loading"
                    class="group p-5 bg-black/60 border border-opacity-40 hover:bg-opacity-10 transition-all text-left relative overflow-hidden disabled:opacity-50"
                    :class="[
                        mode.color === 'upsilon-lime' ? 'border-upsilon-lime hover:border-upsilon-lime hover:bg-upsilon-lime/5' : 'border-upsilon-magenta hover:border-upsilon-magenta hover:bg-upsilon-magenta/5'
                    ]"
                >
                    <span class="block font-scifi text-lg text-white mb-1 uppercase tracking-tighter">{{ mode.name }}</span>
                    <span class="block font-mono text-[9px] uppercase transition-colors"
                        :class="[
                            mode.color === 'upsilon-lime' ? 'text-upsilon-lime group-hover:text-upsilon-lime/70' : 'text-upsilon-magenta group-hover:text-upsilon-magenta/70'
                        ]"
                    >
                        {{ mode.desc }}
                    </span>
                    
                    <!-- Decorative corner in button -->
                    <div class="absolute top-0 right-0 w-2 h-2 border-t border-r opacity-0 group-hover:opacity-100 transition-opacity"
                        :class="mode.color === 'upsilon-lime' ? 'border-upsilon-lime' : 'border-upsilon-magenta'"
                    ></div>
                </button>
            </div>

            <!-- Queued State: Waiting -->
            <div v-else-if="status === 'queued'" class="flex flex-col items-center py-8 space-y-6">
                <div class="relative w-24 h-24">
                    <div class="absolute inset-0 border-2 border-upsilon-cyan/20 rounded-full"></div>
                    <div class="absolute inset-0 border-t-2 border-upsilon-cyan rounded-full animate-spin"></div>
                    <div class="absolute inset-4 border border-upsilon-lime/30 rounded-full animate-pulse"></div>
                    <div class="absolute inset-0 flex items-center justify-center font-scifi text-upsilon-cyan text-xs">
                        {{ matchmakingData?.empty_slots }} REM
                    </div>
                </div>

                <div class="text-center space-y-2">
                    <p class="font-mono text-[10px] text-upsilon-lime uppercase tracking-[0.2em]">
                        Searching for compatible combatants...
                    </p>
                    <p v-if="matchmakingData?.queued_at" class="font-mono text-[8px] text-upsilon-steel uppercase">
                        Active for: {{ (new Date() - new Date(matchmakingData.queued_at)) / 1000 | 0 }}s
                    </p>
                </div>

                <button 
                    @click="leaveQueue"
                    :disabled="loading"
                    class="px-8 py-2 bg-upsilon-magenta/20 border border-upsilon-magenta text-upsilon-magenta font-scifi text-[10px] uppercase tracking-[0.2em] hover:bg-upsilon-magenta hover:text-black transition-all disabled:opacity-50"
                >
                    Abort Engagement
                </button>
            </div>

            <!-- Matched State -->
            <div v-else class="flex flex-col items-center py-8 space-y-4">
                <div class="text-upsilon-lime font-scifi text-2xl animate-bounce uppercase">Target Locked</div>
                <p class="font-mono text-[10px] text-upsilon-cyan uppercase tracking-[0.3em]">Synchronizing Neural Interface...</p>
                <div class="w-full bg-upsilon-cyan/10 h-1 mt-4 relative overflow-hidden">
                    <div class="absolute inset-0 bg-upsilon-cyan animate-progress"></div>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center" v-if="status === 'idle'">
            <p class="text-[10px] font-mono text-upsilon-lime uppercase tracking-[0.4em] animate-pulse">Standing by for command input...</p>
        </div>
    </div>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
@keyframes progress {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
.animate-progress {
    animation: progress 1.5s linear infinite;
}
</style>
