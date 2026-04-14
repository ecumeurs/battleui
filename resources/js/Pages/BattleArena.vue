<!-- @spec-link [[ui_battle_arena]] -->
<!-- @spec-link [[req_ui_look_and_feel]] -->
<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import { getAuthUser } from '@/services/auth';
import { game } from '@/services/game';
import { connection } from '@/services/connection';
import { tactical } from '@/services/tactical';

import CombatHeader from '@/Components/Arena/CombatHeader.vue';
import TeamRosterPanel from '@/Components/Arena/TeamRosterPanel.vue';
import IsoBoardGrid from '@/Components/Arena/IsoBoardGrid.vue';
import ActionPanel from '@/Components/Arena/ActionPanel.vue';
import InitiativeTimeline from '@/Components/Arena/InitiativeTimeline.vue';

const user = ref(getAuthUser());
const matchId = ref(new URLSearchParams(window.location.search).get('match_id'));

const gameState = ref(null);
const matchStartedAt = ref(null);
const isLoading = ref(true);
const isSocketConnected = ref(false);

const myPlayer = computed(() => tactical.myPlayer(gameState.value));
const currentPlayerId = computed(() => myPlayer.value?.nickname || ''); // We use nickname as a stable display ID

let matchTimerInterval = null;
let shotTimerInterval = null;

onMounted(async () => {
    if (!matchId.value) return;

    try {
        const response = await game.fetchGameState(matchId.value);
        gameState.value = response || {};
        matchStartedAt.value = response.started_at;

        game.subscribeToBoard(matchId.value, (event) => {
            console.log('[BoardUpdated]', event);
            gameState.value = event.data;
            connection.setBoardLinked(true);
            // Clear pathfinding/selection on state update
            selectedAction.value = null;
            selectedPath.value = [];
            highlightedCells.value = [];
        });

        // Listen for connection health
        if (window.Echo && window.Echo.connector.pusher.connection) {
            isSocketConnected.value = window.Echo.connector.pusher.connection.state === 'connected';
            window.Echo.connector.pusher.connection.bind('state_change', (states) => {
                isSocketConnected.value = states.current === 'connected';
                console.log('[PusherState]', states.current);
            });
        }

    } catch (e) {
        console.error("Failed to load game state", e);
    } finally {
        isLoading.value = false;
    }

    // Fallback polling for the initial board state if empty
    const emergencyPoller = setInterval(async () => {
        if (allEntities.value.length > 0 || isGameOver.value) {
            clearInterval(emergencyPoller);
            return;
        }
        console.log('[Arena] Board empty, attempting fallback sync...');
        try {
            const response = await game.fetchGameState(matchId.value);
            if (response && response.players && response.players.length > 0) {
                gameState.value = response;
                matchStartedAt.value = response.started_at;
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
});

onUnmounted(() => {
    if (matchId.value) {
        game.unsubscribeFromBoard(matchId.value);
        connection.setBoardLinked(false);
    }
    clearInterval(matchTimerInterval);
    clearInterval(shotTimerInterval);
});

// ─── COMPUTED STATE ──────────────────────────────────────────

const grid = computed(() => gameState.value?.grid || { width: 10, height: 10, cells: [] });
const allEntities = computed(() => {
    if (!gameState.value?.players) return [];
    return gameState.value.players.flatMap(p => p.entities || []);
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

// The player whose character is currently acting (used in ActionPanel header)
const activePlayerName = computed(() => tactical.currentPlayer(gameState.value)?.nickname || '');

const myTeam = computed(() => myPlayer.value?.team || 1);

const allyParticipants = computed(() => tactical.myAllies(gameState.value).concat(myPlayer.value ? [myPlayer.value] : []));
const enemyParticipants = computed(() => tactical.myFoes(gameState.value));

const allyEntities = computed(() => tactical.myCharacters(gameState.value).concat(tactical.myAlliesCharacters(gameState.value)));
const enemyEntities = computed(() => tactical.myFoesCharacters(gameState.value));

function mapParticipantsToRoster(parts) {
    // Note: AI players might not be explicitly in `participants` if they are handled purely in Golang.
    // We will inject a virtual "AI" participant here if needed, but the UI expects a player list structure.
    return parts.map(p => ({
        id: String(p.player_id),
        nickname: p.nickname,
        team: p.team,
        entities: allEntities.value.filter(e => String(e.player_id) === String(p.player_id)).map(e => ({
            ...e,
            _isActive: String(e.id) === currentEntityId.value
        }))
    }));
}

const allyRoster = computed(() => allyParticipants.value.map(p => ({
    nickname: p.nickname,
    team: p.team,
    entities: (p.entities || []).map(e => ({
        ...e,
        _isActive: e.id === currentEntityId.value
    }))
})));
const enemyRoster = computed(() => enemyParticipants.value.map(p => ({
    nickname: p.nickname,
    team: p.team,
    entities: (p.entities || []).map(e => ({
        ...e,
        _isActive: e.id === currentEntityId.value
    }))
})));

const teamColors = computed(() => {
    const colors = {};
    if (!gameState.value?.players) return colors;

    gameState.value.players.forEach(p => {
        const pKey = p.nickname;
        if (p.is_self) {
            colors[pKey] = '#00a8ff'; // Blue
        } else if (p.team === myTeam.value) {
            colors[pKey] = '#39ff13'; // Green
        } else {
            const foes = enemyParticipants.value;
            if (foes[0] && foes[0].nickname === pKey) {
                colors[pKey] = '#ff2020'; // Red
            } else {
                colors[pKey] = '#b030ff'; // Purple
            }
        }
    });

    return colors;
});

const isGameOver = computed(() => {
    return !!gameState.value?.game_finished;
});

const isVictory = computed(() => {
    if (!isGameOver.value) return false;
    if (gameState.value.winner_is_self) return true;
    if (gameState.value.winner_team_id === myTeam.value) return true;
    return false;
});

const allyTeamHp = computed(() => allyEntities.value.reduce((sum, e) => sum + e.hp, 0));
const allyTeamMaxHp = computed(() => allyEntities.value.reduce((sum, e) => sum + e.max_hp, 0));
const allyCharsRemaining = computed(() => allyEntities.value.filter(e => e.hp > 0).length);

const enemyTeamHp = computed(() => enemyEntities.value.reduce((sum, e) => sum + e.hp, 0));
const enemyTeamMaxHp = computed(() => enemyEntities.value.reduce((sum, e) => sum + e.max_hp, 0));
const enemyCharsRemaining = computed(() => enemyEntities.value.filter(e => e.hp > 0).length);

const matchSeconds = ref(0);
const shotClock = ref(0);

const matchDuration = computed(() => {
    const m = Math.floor(matchSeconds.value / 60);
    const s = matchSeconds.value % 60;
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});

const canMove = computed(() => currentEntity.value?.move > 0);
const canAttack = computed(() => currentEntity.value?.hp > 0); // or based on action points if implemented

// ─── ACTION LOGIC ──────────────────────────────────────────
const selectedAction = ref(null);
const selectedPath = ref([]);
const highlightedCells = ref([]);
const isProcessing = ref(false);

async function handleAction(type) {
    if (!isPlayerTurn.value || isProcessing.value) return;
    
    if (type === 'pass') {
        isProcessing.value = true;
        try {
            await game.sendAction(matchId.value, currentEntityId.value, 'pass');
            selectedAction.value = null;
            highlightedCells.value = [];
        } catch (err) {
            console.error("Pass action failed:", err);
        } finally {
            isProcessing.value = false;
        }
        return;
    }

    if (type === 'forfeit') {
        if (confirm("Are you sure you want to forfeit? This will cause a loss for your entire team.")) {
            isProcessing.value = true;
            try {
                await game.forfeit(matchId.value);
            } catch (err) {
                console.error("Forfeit failed:", err);
            } finally {
                isProcessing.value = false;
            }
        }
        return;
    }

    selectedAction.value = type;
    selectedPath.value = [];
    
    if (type === 'move') {
        calculateMoveRange();
    } else if (type === 'attack') {
        calculateAttackRange();
    }
}

function getNeighbors(x, y) {
    const neighbors = [];
    const dirs = [[0, -1], [1, 0], [0, 1], [-1, 0]];
    for (const d of dirs) {
        const nx = x + d[0];
        const ny = y + d[1];
        if (nx >= 0 && nx < grid.value.width && ny >= 0 && ny < grid.value.height) {
            // Check for obstacle
            const cell = grid.value.cells[nx] && grid.value.cells[nx][ny];
            if (cell && !cell.obstacle) {
                // Check if another entity is there (except current active)
                const isOccupied = allEntities.value.some(e => e.id !== currentEntityId.value && e.position.x === nx && e.position.y === ny);
                if (!isOccupied) {
                    neighbors.push({ x: nx, y: ny });
                }
            }
        }
    }
    return neighbors;
}

function findShortestPath(start, target, maxMove) {
    const queue = [[start]];
    const visited = new Set([`${start.x},${start.y}`]);
    
    while(queue.length > 0) {
        const path = queue.shift();
        const curr = path[path.length - 1]; // Current tail node
        
        if (curr.x === target.x && curr.y === target.y) {
            return path.slice(1); // Return path excluding start node
        }
        
        if (path.length - 1 < maxMove) {
            for (const n of getNeighbors(curr.x, curr.y)) {
                const key = `${n.x},${n.y}`;
                if (!visited.has(key)) {
                    visited.add(key);
                    queue.push([...path, {x: n.x, y: n.y}]);
                }
            }
        }
    }
    return null; // Not reachable
}

async function handleTileClick(x, y) {
    if (!isPlayerTurn.value || !selectedAction.value || isProcessing.value) return;
    
    if (selectedAction.value === 'move') {
        const path = findShortestPath(currentEntity.value.position, {x, y}, currentEntity.value.move);
        if (path) {
            selectedPath.value = path;
            isProcessing.value = true;
            try {
                await game.sendAction(matchId.value, currentEntityId.value, 'move', path);
                selectedAction.value = null;
                highlightedCells.value = [];
            } catch (err) {
                console.error("Move action failed:", err);
            } finally {
                isProcessing.value = false;
            }
        }
    } else if (selectedAction.value === 'attack') {
        const targetCell = highlightedCells.value.find(c => c.x === x && c.y === y && c.type === 'attack');
        if (targetCell) {
            isProcessing.value = true;
            try {
                await game.sendAction(matchId.value, currentEntityId.value, 'attack', [{x, y}]);
                selectedAction.value = null;
                highlightedCells.value = [];
            } catch (err) {
                console.error("Attack failed:", err);
            } finally {
                isProcessing.value = false;
            }
        }
    }
}

function calculateMoveRange() {
    if (!currentEntity.value) return;
    const start = currentEntity.value.position;
    const maxMove = currentEntity.value.move;

    const queue = [{ x: start.x, y: start.y, dist: 0 }];
    const visited = new Set([`${start.x},${start.y}`]);
    const highlighted = [];

    while (queue.length > 0) {
       const curr = queue.shift();
       if (curr.x !== start.x || curr.y !== start.y) {
           highlighted.push({ x: curr.x, y: curr.y, type: 'move' });
       }
       
       if (curr.dist < maxMove) {
           for (const n of getNeighbors(curr.x, curr.y)) {
               const key = `${n.x},${n.y}`;
               if (!visited.has(key)) {
                    visited.add(key);
                    queue.push({ x: n.x, y: n.y, dist: curr.dist + 1 });
               }
           }
       }
    }
    highlightedCells.value = highlighted;
}

function calculateAttackRange() {
    if (!currentEntity.value) return;
    const start = currentEntity.value.position;
    
    const dirs = [[0, -1], [1, 0], [0, 1], [-1, 0]];
    const highlighted = [];
    
    for (const d of dirs) {
        const nx = start.x + d[0];
        const ny = start.y + d[1];
        if (nx >= 0 && nx < grid.value.width && ny >= 0 && ny < grid.value.height) {
             const enemy = enemyEntities.value.find(e => e.position.x === nx && e.position.y === ny && e.hp > 0);
             if (enemy) {
                 highlighted.push({ x: nx, y: ny, type: 'attack' });
             }
        }
    }
    highlightedCells.value = highlighted;
}
</script>

<template>
    <Head title="Battle Arena | Combat Engaged" />

    <TacticalLayout v-if="user && !isLoading" :user="user">
        <div class="arena">
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
                    <IsoBoardGrid
                        :grid="grid"
                        :entities="allEntities"
                        :current-entity-id="currentEntityId"
                        :team-colors="teamColors"
                        :highlighted-cells="highlightedCells"
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
                <div class="game-over-content" :class="{ 'game-over--victory': isVictory, 'game-over--defeat': !isVictory }">
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
    background: repeating-linear-gradient(
        0deg,
        transparent,
        transparent 3px,
        rgba(0, 0, 0, 0.03) 3px,
        rgba(0, 0, 0, 0.03) 6px
    );
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
    font-size: 8px;
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
.game-over--victory::before { background: #00f2ff; box-shadow: 0 0 20px #00f2ff; }
.game-over--victory .game-over__title { color: #00f2ff; text-shadow: 0 0 16px rgba(0, 242, 255, 0.6); }

.game-over--defeat {
    border-color: rgba(255, 32, 32, 0.4);
}
.game-over--defeat::before { background: #ff2020; box-shadow: 0 0 20px #ff2020; }
.game-over--defeat .game-over__title { color: #ff2020; text-shadow: 0 0 16px rgba(255, 32, 32, 0.6); }

.game-over__title {
    font-family: 'Orbitron', sans-serif;
    font-size: 64px;
    font-weight: 800;
    letter-spacing: 0.15em;
    margin: 0 0 10px 0;
}

.game-over__subtitle {
    font-family: 'JetBrains Mono', monospace;
    font-size: 14px;
    color: #e0e0e0;
    margin: 0 0 40px 0;
    letter-spacing: 0.05em;
    opacity: 0.8;
}

.action-btn-back {
    display: inline-block;
    font-family: 'Orbitron', sans-serif;
    font-size: 14px;
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
</style>
