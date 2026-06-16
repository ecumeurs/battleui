<!-- @spec-link [[ui_battle_arena]] -->
<!-- @spec-link [[req_ui_look_and_feel]] -->
<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import { getAuthUser } from '@/services/auth';
import { game } from '@/services/game';

import CombatHeader from '@/Components/Arena/CombatHeader.vue';
import TeamRosterPanel from '@/Components/Arena/TeamRosterPanel.vue';
import ThreeGrid from '@/Components/Arena/ThreeGrid.vue';
import ActionPanel from '@/Components/Arena/ActionPanel.vue';
import InitiativeTimeline from '@/Components/Arena/InitiativeTimeline.vue';
import TacticalActionReport from '@/Components/Arena/TacticalActionReport.vue';
import ConfirmModal from '@/Components/Shared/Modals/ConfirmModal.vue';

import { useBattleChannel } from '@/composables/useBattleChannel';
import { useBoardState }    from '@/composables/useBoardState';
import { useActionDispatch } from '@/composables/useActionDispatch';

// ─── Core state ───────────────────────────────────────────────────────────────
const user     = ref(getAuthUser());
const matchId  = ref(new URLSearchParams(window.location.search).get('match_id'));

const gameState      = ref(null);
const matchStartedAt = ref(null);
const isLoading      = ref(true);
const error          = ref(null);

const lastAction       = ref(null);
const showActionReport = ref(false);
const animAction       = ref(null);
let actionTimeout = null;
let animTimeout   = null;

// ─── Board state composable ───────────────────────────────────────────────────
const {
    matchSeconds,
    shotClock,
    myPlayer,
    currentPlayerId,
    grid,
    allEntities,
    turnOrder,
    currentEntityId,
    currentEntity,
    isPlayerTurn,
    activePlayerName,
    myTeam,
    allyParticipants,
    enemyParticipants,
    allyEntities,
    enemyEntities,
    allyRoster,
    enemyRoster,
    teamColors,
    isGameOver,
    isVictory,
    allyTeamHp,
    allyTeamMaxHp,
    allyCharsRemaining,
    enemyTeamHp,
    enemyTeamMaxHp,
    enemyCharsRemaining,
    matchDuration,
    canMove,
    canAttack,
} = useBoardState(gameState);

// ─── Action dispatch composable ───────────────────────────────────────────────
const {
    selectedAction,
    selectedPath,
    highlightedCells,
    isProcessing,
    showForfeitModal,
    handleAction,
    handleTileClick,
    handleKeydown,
    executeForfeit,
} = useActionDispatch(
    matchId,
    gameState,
    currentEntityId,
    currentEntity,
    isPlayerTurn,
    allEntities,
    enemyEntities,
    allyEntities,
    grid,
);

// ─── WebSocket channel composable ────────────────────────────────────────────
const { isSocketConnected, wireConnectionHealth } = useBattleChannel(
    matchId,
    gameState,
    selectedAction,
    selectedPath,
    highlightedCells,
);

// ─── Mount: initial fetch + timers + keyboard ─────────────────────────────────
let matchTimerInterval = null;
let shotTimerInterval  = null;

onMounted(async () => {
    if (!matchId.value) return;

    // Fetch initial state
    try {
        const response = await game.fetchGameState(matchId.value);
        console.log('[Arena] Initial fetch response:', response);
        if (response && response.game_state) {
            if (!gameState.value || response.game_state.version >= (gameState.value.version || 0)) {
                gameState.value      = response.game_state;
                matchStartedAt.value = response.started_at;
            } else {
                console.warn('[Arena] Ignoring stale initial fetch (Version', response.game_state.version, ') as socket already provided Version', gameState.value.version);
            }
        } else {
            console.warn('[Arena] No game_state found in response!');
        }

        wireConnectionHealth();

        // Watch for actions → feedback + animation
        watch(() => gameState.value?.action, (newAction) => {
            if (newAction && newAction.type) {
                lastAction.value = newAction;
                showActionReport.value = true;
                if (actionTimeout) clearTimeout(actionTimeout);
                actionTimeout = setTimeout(() => { showActionReport.value = false; }, 3000);

                animAction.value = newAction;
                if (animTimeout) clearTimeout(animTimeout);
                animTimeout = setTimeout(() => { animAction.value = null; }, 800);
            }
        }, { deep: true });

    } catch (e) {
        console.error('Failed to load game state', e);
        error.value = 'CRITICAL: Failed to establish tactical link with Game Engine.';
    } finally {
        isLoading.value = false;
    }

    // Fallback polling: refetch if board is still empty after 2 s intervals
    const emergencyPoller = setInterval(async () => {
        if (allEntities.value.length > 0 || isGameOver.value) {
            clearInterval(emergencyPoller);
            return;
        }
        console.log('[Arena] Board empty, attempting fallback sync...');
        try {
            const response = await game.fetchGameState(matchId.value);
            if (response && response.game_state && response.game_state.players) {
                if (!gameState.value || response.game_state.version >= (gameState.value.version || 0)) {
                    gameState.value      = response.game_state;
                    matchStartedAt.value = response.started_at;
                }
                clearInterval(emergencyPoller);
            }
        } catch (err) {
            console.error('[Arena] Fallback sync failed', err);
        }
    }, 2000);

    matchTimerInterval = setInterval(() => {
        if (!matchStartedAt.value) return;
        const start = new Date(matchStartedAt.value).getTime();
        matchSeconds.value = Math.max(0, Math.floor((Date.now() - start) / 1000));
    }, 1000);

    shotTimerInterval = setInterval(() => {
        if (!gameState.value?.timeout) return;
        const to = new Date(gameState.value.timeout).getTime();
        shotClock.value = Math.max(0, Math.ceil((to - Date.now()) / 1000));
    }, 1000);

    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    clearInterval(matchTimerInterval);
    clearInterval(shotTimerInterval);
    if (actionTimeout) clearTimeout(actionTimeout);
    if (animTimeout)   clearTimeout(animTimeout);
    window.removeEventListener('keydown', handleKeydown);
    // Channel cleanup is handled by useBattleChannel's own onUnmounted
});
</script>

<template>

    <Head title="Battle Arena | Combat Engaged" />

    <TacticalLayout v-if="user && !isLoading" :user="user">
        <div v-if="error" class="arena-error">
            <div class="arena-error__content">
                <h1 class="text-red-500 font-bold mb-4">TACTICAL LINK FAILURE</h1>
                <p>{{ error }}</p>
                <div class="mt-8">
                    <a href="/dashboard" class="action-btn-back">ABORT MISSION</a>
                </div>
            </div>
        </div>
        <div v-else class="arena">
            <!-- COMBAT HEADER -->
            <CombatHeader
                :ally-team-hp="allyTeamHp"
                :ally-team-max-hp="allyTeamMaxHp"
                :ally-chars-remaining="allyCharsRemaining"
                :ally-total-chars="allyEntities.length"
                :enemy-team-hp="enemyTeamHp"
                :enemy-team-max-hp="enemyTeamMaxHp"
                :enemy-chars-remaining="enemyCharsRemaining"
                :enemy-total-chars="enemyEntities.length"
                :match-duration="matchDuration"
                :shot-clock="shotClock"
                :is-socket-connected="isSocketConnected"
            />

            <!-- ACTION REPORT OVERLAY -->
            <TacticalActionReport :action="lastAction" :show="showActionReport" />

            <!-- Animation presence markers (pointer-events:none; used by Playwright + drives Pawn3D flash) -->
            <div v-if="animAction?.type === 'attack'" data-testid="anim-attack" class="anim-marker" />
            <div v-if="animAction?.type === 'skill'"  data-testid="anim-skill"  class="anim-marker" />

            <!-- MAIN CONTENT: Rosters + Board -->
            <div class="arena__body">
                <!-- LEFT ROSTER: Allied Forces -->
                <TeamRosterPanel
                    :players="allyRoster"
                    :detailed-player-id="currentPlayerId"
                    :team-colors="teamColors"
                    side="left"
                />

                <!-- CENTER: Board + Actions -->
                <div class="arena__center">
                    <ThreeGrid
                        :grid="grid"
                        :entities="allEntities"
                        :current-entity-id="currentEntityId"
                        :team-colors="teamColors"
                        :highlighted-cells="highlightedCells"
                        :anim-action="animAction"
                        effects
                        @tile-click="handleTileClick"
                    />

                    <ActionPanel
                        :is-player-turn="isPlayerTurn"
                        :is-processing="isProcessing"
                        :selected-action="selectedAction"
                        :active-character="currentEntity"
                        :active-player-name="activePlayerName"
                        :can-move="canMove"
                        :can-attack="canAttack"
                        :equipped-skills="currentEntity?.equipped_skills ?? []"
                        @action="handleAction"
                    />
                </div>

                <!-- RIGHT ROSTER: Hostile Forces -->
                <TeamRosterPanel
                    :players="enemyRoster"
                    detailed-player-id=""
                    :team-colors="teamColors"
                    side="right"
                />
            </div>

            <!-- INITIATIVE TIMELINE -->
            <InitiativeTimeline
                :turns="turnOrder"
                :team-colors="teamColors"
                :current-entity-id="currentEntityId"
            />

            <!-- Match ID watermark -->
            <div class="arena__watermark">
                MATCH {{ matchId }}
            </div>

            <!-- GAME OVER OVERLAY -->
            <div v-if="isGameOver" class="game-over-overlay">
                <div class="game-over-content"
                    :class="{ 'game-over--victory': isVictory, 'game-over--defeat': !isVictory }">
                    <h1 class="game-over__title">
                        {{ isVictory ? 'VICTORY' : 'DEFEAT' }}
                    </h1>
                    <p class="game-over__subtitle">
                        {{ isVictory ? 'Opposing forces eliminated.' : 'Squad wiped out.' }}
                    </p>
                    <div class="game-over__actions">
                        <a href="/dashboard" class="action-btn-back">RETURN TO DASHBOARD</a>
                    </div>
                </div>
            </div>

            <!-- FORFEIT CONFIRMATION -->
            <ConfirmModal
                :show="showForfeitModal"
                title="TERMINATION REQUEST"
                message="Are you sure you want to forfeit? This will cause an immediate loss for your entire team and disconnect all tactical links."
                confirm-text="FORFEIT MATCH"
                cancel-text="RESUME COMBAT"
                type="danger"
                @close="showForfeitModal = false"
                @confirm="executeForfeit"
            />
        </div>
    </TacticalLayout>
</template>

<style scoped>
.arena {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
    position: relative;
    overflow: hidden;
}

/* Background effects */
.arena::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse at 20% 50%, rgba(0, 168, 255, 0.03) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 50%, rgba(255, 32, 32, 0.03) 0%, transparent 50%);
    pointer-events: none;
    z-index: 0;
}

/* Scanline overlay */
.arena::after {
    content: '';
    position: absolute;
    inset: 0;
    background: repeating-linear-gradient(0deg,
            transparent,
            transparent 3px,
            rgba(0, 0, 0, 0.03) 3px,
            rgba(0, 0, 0, 0.03) 6px);
    pointer-events: none;
    z-index: 100;
}

.arena__body {
    flex: 1;
    display: flex;
    min-height: 0;
    position: relative;
    z-index: 1;
}

.arena__center {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
    min-width: 0;
}

.arena__watermark {
    position: absolute;
    bottom: 60px;
    right: 240px;
    font-family: 'Orbitron', sans-serif;
    font-size: var(--fs-xs);
    letter-spacing: 0.3em;
    text-transform: uppercase;
    color: rgba(74, 74, 79, 0.15);
    pointer-events: none;
    z-index: 50;
}

/* ─── GAME OVER OVERLAY ─── */
.game-over-overlay {
    position: absolute;
    inset: 0;
    z-index: 1000;
    background: rgba(10, 10, 11, 0.85);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
}

.game-over-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 60px 100px;
    background: rgba(22, 22, 28, 0.9);
    border: 1px solid;
    box-shadow: 0 0 40px rgba(0, 0, 0, 0.8);
    position: relative;
    overflow: hidden;
}

.game-over-content::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

.game-over--victory {
    border-color: rgba(0, 242, 255, 0.4);
}

.game-over--victory::before {
    background: var(--color-cyan);
    box-shadow: 0 0 20px var(--color-cyan);
}

.game-over--victory .game-over__title {
    color: var(--color-cyan);
    text-shadow: 0 0 16px rgba(0, 242, 255, 0.6);
}

.game-over--defeat {
    border-color: rgba(255, 32, 32, 0.4);
}

.game-over--defeat::before {
    background: var(--color-red);
    box-shadow: 0 0 20px var(--color-red);
}

.game-over--defeat .game-over__title {
    color: var(--color-red);
    text-shadow: 0 0 16px rgba(255, 32, 32, 0.6);
}

.game-over__title {
    font-family: 'Orbitron', sans-serif;
    font-size: 64px;
    font-weight: 800;
    letter-spacing: 0.15em;
    margin: 0 0 10px 0;
}

.game-over__subtitle {
    font-family: 'JetBrains Mono', monospace;
    font-size: var(--fs-sm);
    color: var(--color-text);
    margin: 0 0 40px 0;
    letter-spacing: 0.05em;
    opacity: 0.8;
}

.action-btn-back {
    display: inline-block;
    font-family: 'Orbitron', sans-serif;
    font-size: var(--fs-sm);
    letter-spacing: 0.1em;
    color: #fff;
    text-decoration: none;
    padding: 12px 32px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.2s ease;
}

.action-btn-back:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: #fff;
    box-shadow: 0 0 12px rgba(255, 255, 255, 0.3);
}

.arena-error {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(10, 10, 11, 0.9);
    backdrop-filter: blur(10px);
    z-index: 2000;
}

.arena-error__content {
    background: rgba(22, 22, 28, 0.95);
    border: 1px solid rgba(255, 32, 32, 0.4);
    padding: 60px;
    text-align: center;
    box-shadow: 0 0 50px rgba(255, 0, 0, 0.1);
    max-width: 600px;
}

.arena-error__content h1 {
    font-family: 'Orbitron', sans-serif;
    font-size: 32px;
    letter-spacing: 0.2em;
    color: var(--color-red);
    text-shadow: 0 0 10px rgba(255, 32, 32, 0.5);
}

.arena-error__content p {
    font-family: 'JetBrains Mono', monospace;
    font-size: var(--fs-md);
    color: #e0e0e0;
    line-height: 1.6;
    margin-bottom: 30px;
}

.anim-marker {
    position: absolute;
    pointer-events: none;
    top: 0;
    left: 0;
    width: 1px;
    height: 1px;
    opacity: 0;
}
</style>
