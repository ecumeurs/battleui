<!-- @spec-link [[mech_frontend_test_seams]] -->
<!-- Battle Arena Sandbox — fixture-driven test harness for arena logic & UI. -->
<!-- No auth required. No API calls. No WebSocket. -->
<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import { tactical } from '@/services/tactical';

import CombatHeader from '@/Components/Arena/CombatHeader.vue';
import TeamRosterPanel from '@/Components/Arena/TeamRosterPanel.vue';
import ThreeGrid from '@/Components/Arena/ThreeGrid.vue';
import ActionPanel from '@/Components/Arena/ActionPanel.vue';
import InitiativeTimeline from '@/Components/Arena/InitiativeTimeline.vue';
import TacticalActionReport from '@/Components/Arena/TacticalActionReport.vue';

import { SCENARIOS } from './battleSandboxScenarios.js';
import { TEAM_COLORS } from '@/constants/theme.js';

const props = defineProps({
    name: { type: String, required: true },
});

// ─── Scenario loading ────────────────────────────────────────────────────────

const scenario = SCENARIOS[props.name];
const isVisual = new URLSearchParams(window.location.search).get('visual') === '1';
const initialStep = parseInt(new URLSearchParams(window.location.search).get('step') ?? '0', 10);

const currentStep = ref(Math.max(0, isNaN(initialStep) ? 0 : initialStep));
const gameState = computed(() => scenario?.steps?.[currentStep.value] ?? null);

// ─── Mock user (sandbox always shows UI as self) ─────────────────────────────

const mockUser = { id: 1, account_name: 'sandbox_user', email: 'sandbox@dev', ws_channel_key: 'sandbox' };

// ─── Computed game state (mirrors BattleArena.vue) ───────────────────────────

const myPlayer = computed(() => tactical.myPlayer(gameState.value));
const currentPlayerId = computed(() => {
    if (!myPlayer.value) return '';
    return String(myPlayer.value.player_id || myPlayer.value.nickname);
});

const grid = computed(() => gameState.value?.grid || null);
const allEntities = computed(() => {
    if (!gameState.value?.players) return [];
    return gameState.value.players.flatMap(p => p.entities);
});

const turnOrder = computed(() => {
    if (!gameState.value?.turn) return [];
    return gameState.value.turn.map(t => {
        const ent = allEntities.value.find(e => e.id === t.entity_id);
        return { ...t, name: ent ? ent.name : 'Unknown' };
    });
});

const currentEntityId = computed(() => gameState.value?.current_entity_id || '');
const currentEntity = computed(() => tactical.currentCharacter(gameState.value));
const isPlayerTurn = computed(() => !!gameState.value?.current_player_is_self);
const activePlayerName = computed(() => tactical.currentPlayer(gameState.value)?.nickname || '');
const myTeam = computed(() => myPlayer.value?.team || 1);

const allyParticipants = computed(() => tactical.myAllies(gameState.value).concat(myPlayer.value ? [myPlayer.value] : []));
const enemyParticipants = computed(() => tactical.myFoes(gameState.value));

const allyEntities = computed(() => tactical.myCharacters(gameState.value).concat(tactical.myAlliesCharacters(gameState.value)));
const enemyEntities = computed(() => tactical.myFoesCharacters(gameState.value));

function mapParticipantsToRoster(parts) {
    return parts.map(p => ({
        id: String(p.player_id || p.nickname),
        nickname: p.nickname,
        team: p.team,
        entities: (p.entities || []).map(e => ({ ...e, _isActive: String(e.id) === currentEntityId.value }))
    }));
}

const allyRoster = computed(() => mapParticipantsToRoster(allyParticipants.value));
const enemyRoster = computed(() => mapParticipantsToRoster(enemyParticipants.value));

const teamColors = computed(() => {
    const colors = {};
    if (!gameState.value?.players) return colors;
    gameState.value.players.forEach(p => {
        let color = '#ffffff';
        if (p.is_self)                        color = TEAM_COLORS.self;
        else if (p.team === myTeam.value)     color = TEAM_COLORS.ally;
        else if (enemyParticipants.value[0] && (enemyParticipants.value[0].player_id === p.player_id || enemyParticipants.value[0].nickname === p.nickname))
                                               color = TEAM_COLORS.enemy;
        else                                  color = TEAM_COLORS.enemy2;
        if (p.player_id) colors[String(p.player_id)] = color;
        if (p.nickname)  colors[String(p.nickname)]  = color;
    });
    return colors;
});

const isGameOver = computed(() => !!gameState.value?.game_finished);
const isVictory = computed(() => tactical.isWinner(gameState.value));

const allyTeamHp       = computed(() => allyEntities.value.reduce((s, e) => s + e.hp, 0));
const allyTeamMaxHp    = computed(() => allyEntities.value.reduce((s, e) => s + e.max_hp, 0));
const allyCharsRemaining = computed(() => allyEntities.value.filter(e => e.hp > 0).length);
const enemyTeamHp      = computed(() => enemyEntities.value.reduce((s, e) => s + e.hp, 0));
const enemyTeamMaxHp   = computed(() => enemyEntities.value.reduce((s, e) => s + e.max_hp, 0));
const enemyCharsRemaining = computed(() => enemyEntities.value.filter(e => e.hp > 0).length);

const canMove   = computed(() => (currentEntity.value?.move ?? 0) > 0);
const canAttack = computed(() => (currentEntity.value?.hp ?? 0) > 0);

// ─── Sandbox action simulation (no real API calls) ───────────────────────────

const selectedAction = ref(null);
const highlightedCells = ref([]);
const isProcessing = ref(false);

const animAction = ref(null);
let animTimeout = null;

// Trigger animAction when step changes and new state has an action
watch(currentStep, () => {
    const a = gameState.value?.action;
    if (a?.type) {
        animAction.value = a;
        if (animTimeout) clearTimeout(animTimeout);
        animTimeout = setTimeout(() => { animAction.value = null; }, 800);
    }
    selectedAction.value = null;
    highlightedCells.value = [];
});

// LOS: obstacles and live entities (except caster) block sight
function hasLOS(sx, sy, tx, ty) {
    const dx = tx - sx;
    const dy = ty - sy;
    const dist = Math.max(Math.abs(dx), Math.abs(dy));
    if (dist <= 1) return true;
    for (let i = 1; i < dist; i++) {
        const cx = Math.round(sx + dx * i / dist);
        const cy = Math.round(sy + dy * i / dist);
        if (grid.value.cells[cx]?.[cy]?.obstacle) return false;
        if (allEntities.value.some(e => e.id !== currentEntityId.value && e.position.x === cx && e.position.y === cy && e.hp > 0)) return false;
    }
    return true;
}

// Show full zone diamond — target-type filtering deferred to click time
function calculateSkillRange(skill) {
    if (!currentEntity.value || !grid.value) return;
    const start = currentEntity.value.position;
    const range = skill.targeting?.Range?.value ?? 1;
    const highlighted = [];

    for (let dx = -range; dx <= range; dx++) {
        for (let dy = -range; dy <= range; dy++) {
            if (dx === 0 && dy === 0) continue;
            if (Math.abs(dx) + Math.abs(dy) > range) continue;
            const nx = start.x + dx, ny = start.y + dy;
            if (nx < 0 || nx >= grid.value.width || ny < 0 || ny >= grid.value.height) continue;
            const cell = grid.value.cells[nx]?.[ny];
            if (!cell || cell.obstacle) continue;
            if (!hasLOS(start.x, start.y, nx, ny)) continue;
            highlighted.push({ x: nx, y: ny, type: 'skill' });
        }
    }
    highlightedCells.value = highlighted;
}

function calculateMoveRange() {
    if (!currentEntity.value || !grid.value) return;
    const start = currentEntity.value.position;
    const maxMove = currentEntity.value.move;
    const queue = [{ x: start.x, y: start.y, dist: 0 }];
    const visited = new Set([`${start.x},${start.y}`]);
    const highlighted = [];

    while (queue.length > 0) {
        const curr = queue.shift();
        if (curr.x !== start.x || curr.y !== start.y) highlighted.push({ x: curr.x, y: curr.y, type: 'move' });
        if (curr.dist < maxMove) {
            const dirs = [[0,-1],[1,0],[0,1],[-1,0]];
            for (const d of dirs) {
                const nx = curr.x + d[0], ny = curr.y + d[1];
                if (nx >= 0 && nx < grid.value.width && ny >= 0 && ny < grid.value.height) {
                    const cell = grid.value.cells[nx]?.[ny];
                    if (cell && !cell.obstacle) {
                        const key = `${nx},${ny}`;
                        if (!visited.has(key)) { visited.add(key); queue.push({ x: nx, y: ny, dist: curr.dist + 1 }); }
                    }
                }
            }
        }
    }
    highlightedCells.value = highlighted;
}

function calculateAttackRange() {
    if (!currentEntity.value || !grid.value) return;
    const start = currentEntity.value.position;
    const dirs = [[0,-1],[1,0],[0,1],[-1,0]];
    const highlighted = [];
    for (const d of dirs) {
        const nx = start.x + d[0], ny = start.y + d[1];
        if (nx >= 0 && nx < grid.value.width && ny >= 0 && ny < grid.value.height) {
            const cell = grid.value.cells[nx]?.[ny];
            if (cell && !cell.obstacle) highlighted.push({ x: nx, y: ny, type: 'attack' });
        }
    }
    highlightedCells.value = highlighted;
}

function handleAction(type) {
    if (!isPlayerTurn.value || isProcessing.value) return;
    if (typeof type === 'object' && type.type === 'skill') {
        const skill = type.skill;
        if (!skill || skill.behavior === 'Passive' || skill.behavior === 'Reaction' || skill.behavior === 'Counter') return;
        const targetType = skill.targeting?.TargetType?.value ?? 'Entity';
        if (skill.behavior === 'Direct' && targetType === 'Self') {
            // In sandbox: just log — no real action is sent
            console.log('[Sandbox] Self-skill activated:', skill.name);
            selectedAction.value = null;
            return;
        }
        selectedAction.value = { type: 'skill', skill };
        calculateSkillRange(skill);
        return;
    }
    if (type === 'forfeit') return;
    selectedAction.value = type;
    if (type === 'move')   calculateMoveRange();
    if (type === 'attack') calculateAttackRange();
}

function handleTileClick(x, y) {
    if (!isPlayerTurn.value || !selectedAction.value || isProcessing.value) return;
    // In sandbox, tile clicks just clear the selection (no state mutation)
    console.log('[Sandbox] Tile clicked at', x, y, 'while action =', selectedAction.value);
    selectedAction.value = null;
    highlightedCells.value = [];
}

// ─── Keyboard shortcuts ──────────────────────────────────────────────────────

function handleKeydown(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
    if (e.key === 'Escape') { selectedAction.value = null; highlightedCells.value = []; return; }
    if (!isPlayerTurn.value || isProcessing.value) return;
    if (e.code === 'KeyM') handleAction('move');
    else if (e.code === 'KeyA') handleAction('attack');
    else if (e.code === 'KeyP' || e.code === 'Space') { e.preventDefault(); handleAction('pass'); }
    else if (e.key >= '1' && e.key <= '5') {
        const idx = parseInt(e.key) - 1;
        const actionable = (currentEntity.value?.equipped_skills ?? []).filter(
            s => s.behavior === 'Direct' || s.behavior === 'Trap',
        );
        if (actionable[idx]) handleAction({ type: 'skill', skill: actionable[idx] });
    }
}

// ─── Step navigation ─────────────────────────────────────────────────────────

function nextStep() { if (currentStep.value < scenario.steps.length - 1) currentStep.value++; }
function prevStep() { if (currentStep.value > 0) currentStep.value--; }

// ─── Playwright seam ─────────────────────────────────────────────────────────

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    window.__upsilonDebug = {
        ready: true,
        frozen: !isVisual,
        sandbox: true,
        scenario: props.name,
        get currentStep() { return currentStep.value; },
        get totalSteps()  { return scenario?.steps?.length ?? 0; },
        nextStep,
        prevStep,
        getState: () => gameState.value,
    };
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    if (animTimeout) clearTimeout(animTimeout);
    delete window.__upsilonDebug;
});
</script>

<template>
    <Head :title="`[SANDBOX] ${scenario?.label ?? name}`" />

    <div v-if="!scenario" style="padding: 40px; color: #ff2020; font-family: monospace;">
        Unknown scenario: <strong>{{ name }}</strong>.
        <a href="/__test/battle/" style="color: #00f2ff;">← Back to scenario list</a>
    </div>

    <TacticalLayout v-else :user="mockUser">

        <!-- Sandbox step indicator -->
        <div class="sandbox-hud">
            <a href="/__test/battle/" class="sandbox-hud__back">← SANDBOX</a>
            <span class="sandbox-hud__scenario">{{ scenario.label }}</span>
            <div class="sandbox-hud__steps">
                <button class="sandbox-hud__btn" :disabled="currentStep === 0" @click="prevStep">◀</button>
                <span class="sandbox-hud__step-count">STEP {{ currentStep + 1 }} / {{ scenario.steps.length }}</span>
                <button class="sandbox-hud__btn" :disabled="currentStep === scenario.steps.length - 1" @click="nextStep">▶</button>
            </div>
        </div>

        <div class="arena">

            <!-- COMBAT HEADER -->
            <CombatHeader
                :ally-team-hp="allyTeamHp" :ally-team-max-hp="allyTeamMaxHp"
                :ally-chars-remaining="allyCharsRemaining" :ally-total-chars="allyEntities.length"
                :enemy-team-hp="enemyTeamHp" :enemy-team-max-hp="enemyTeamMaxHp"
                :enemy-chars-remaining="enemyCharsRemaining" :enemy-total-chars="enemyEntities.length"
                match-duration="00:00" :shot-clock="0" :is-socket-connected="false"
            />

            <!-- ACTION REPORT OVERLAY -->
            <TacticalActionReport :action="gameState?.action" :show="!!gameState?.action?.type" />

            <!-- Animation markers (Playwright detectable) -->
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
                        v-if="grid"
                        :grid="grid"
                        :entities="allEntities"
                        :current-entity-id="currentEntityId"
                        :team-colors="teamColors"
                        :highlighted-cells="highlightedCells"
                        :anim-action="animAction"
                        :auto-rotate="isVisual"
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

            <!-- GAME OVER OVERLAY -->
            <div v-if="isGameOver" class="game-over-overlay">
                <div class="game-over-content"
                     :class="{ 'game-over--victory': isVictory, 'game-over--defeat': !isVictory }">
                    <h1 class="game-over__title">{{ isVictory ? 'VICTORY' : 'DEFEAT' }}</h1>
                    <p class="game-over__subtitle">{{ isVictory ? 'Opposing forces eliminated.' : 'Squad wiped out.' }}</p>
                    <div class="game-over__actions">
                        <a href="/__test/battle/" class="action-btn-back">RETURN TO SCENARIOS</a>
                    </div>
                </div>
            </div>

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

/* Sandbox HUD bar */
.sandbox-hud {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 6px 14px;
    background: rgba(251, 191, 36, 0.08);
    border-bottom: 1px solid rgba(251, 191, 36, 0.3);
    font-family: 'JetBrains Mono', monospace;
    font-size: var(--fs-xs);
    color: var(--color-gold);
    position: relative;
    z-index: 1001;
}

.sandbox-hud__back {
    color: var(--color-gold);
    text-decoration: none;
    opacity: 0.7;
}

.sandbox-hud__back:hover { opacity: 1; }

.sandbox-hud__scenario {
    flex: 1;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.sandbox-hud__steps {
    display: flex;
    align-items: center;
    gap: 8px;
}

.sandbox-hud__btn {
    background: rgba(251, 191, 36, 0.1);
    border: 1px solid rgba(251, 191, 36, 0.3);
    color: var(--color-gold);
    cursor: pointer;
    padding: 2px 10px;
    font-family: inherit;
    font-size: var(--fs-xs);
}

.sandbox-hud__btn:disabled { opacity: 0.3; cursor: not-allowed; }
.sandbox-hud__btn:not(:disabled):hover { background: rgba(251, 191, 36, 0.2); }

.sandbox-hud__step-count {
    letter-spacing: 0.08em;
}

.anim-marker {
    position: absolute;
    pointer-events: none;
    top: 0; left: 0;
    width: 1px; height: 1px;
    opacity: 0;
}

/* Game over overlay (mirrors BattleArena.vue) */
.game-over-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(10,10,11,0.85);
    backdrop-filter: blur(10px);
    z-index: 2000;
}

.game-over-content {
    text-align: center;
    padding: 60px 80px;
    border: 1px solid rgba(255,255,255,0.1);
    position: relative;
}

.game-over--victory { border-color: rgba(0,242,255,0.4); }
.game-over--defeat  { border-color: rgba(255,32,32,0.4); }

.game-over__title {
    font-family: 'Orbitron', sans-serif;
    font-size: 64px;
    font-weight: 800;
    letter-spacing: 0.15em;
    margin: 0 0 10px;
}

.game-over--victory .game-over__title { color: var(--color-cyan); }
.game-over--defeat  .game-over__title { color: var(--color-red); }

.game-over__subtitle {
    font-family: 'JetBrains Mono', monospace;
    font-size: var(--fs-sm);
    color: var(--color-text);
    letter-spacing: 0.05em;
    margin-bottom: 40px;
}

.game-over__actions { display: flex; justify-content: center; }

.action-btn-back {
    font-family: 'Orbitron', sans-serif;
    font-size: var(--fs-sm);
    letter-spacing: 0.1em;
    color: #fff;
    text-decoration: none;
    padding: 12px 32px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.2);
    transition: all 0.2s ease;
}

.action-btn-back:hover {
    background: rgba(255,255,255,0.1);
    border-color: #fff;
    box-shadow: 0 0 12px rgba(255,255,255,0.3);
}
</style>
