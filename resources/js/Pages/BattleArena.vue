<!-- @spec-link [[ui_battle_arena]] -->
<!-- @spec-link [[req_ui_look_and_feel]] -->
<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import { getAuthUser } from '@/services/auth';

import CombatHeader from '@/Components/Arena/CombatHeader.vue';
import TeamRosterPanel from '@/Components/Arena/TeamRosterPanel.vue';
import IsoBoardGrid from '@/Components/Arena/IsoBoardGrid.vue';
import ActionPanel from '@/Components/Arena/ActionPanel.vue';
import InitiativeTimeline from '@/Components/Arena/InitiativeTimeline.vue';

const user = ref(getAuthUser());
const matchId = ref(new URLSearchParams(window.location.search).get('match_id') || 'MOCK-ARENA-001');

// ─── MOCK DATA ──────────────────────────────────────────
// Mirrors the Go engine's BoardState + enriched player data.

const CURRENT_PLAYER_ID = 'p1';

const mockPlayers = ref({
    ally: [
        {
            id: 'p1',
            nickname: 'NeonWraith',
            team: 1,
            entities: [
                { id: 'e1', player_id: 'p1', name: 'Vex-7', hp: 12, max_hp: 15, attack: 5, defense: 3, move: 3, max_move: 5, position: { x: 1, y: 2 }, _isActive: true },
                { id: 'e2', player_id: 'p1', name: 'Kira', hp: 8, max_hp: 10, attack: 6, defense: 2, move: 4, max_move: 4, position: { x: 2, y: 4 }, _isActive: false },
                { id: 'e3', player_id: 'p1', name: 'Bolt-9', hp: 18, max_hp: 20, attack: 3, defense: 5, move: 2, max_move: 3, position: { x: 0, y: 3 }, _isActive: false },
            ]
        },
        {
            id: 'p2',
            nickname: 'DustRunner',
            team: 1,
            entities: [
                { id: 'e7', player_id: 'p2', name: 'Ash-3', hp: 14, max_hp: 14, attack: 4, defense: 4, move: 3, max_move: 3, position: { x: 1, y: 6 }, _isActive: false },
                { id: 'e8', player_id: 'p2', name: 'Flint', hp: 9, max_hp: 12, attack: 5, defense: 3, move: 5, max_move: 5, position: { x: 3, y: 5 }, _isActive: false },
                { id: 'e9', player_id: 'p2', name: 'Pulse', hp: 11, max_hp: 11, attack: 3, defense: 5, move: 2, max_move: 4, position: { x: 2, y: 7 }, _isActive: false },
            ]
        }
    ],
    enemy: [
        {
            id: 'p3',
            nickname: 'IronVeil',
            team: 2,
            entities: [
                { id: 'e4', player_id: 'p3', name: 'Rex-4', hp: 6, max_hp: 14, attack: 4, defense: 4, move: 3, max_move: 3, position: { x: 8, y: 7 }, _isActive: false },
                { id: 'e5', player_id: 'p3', name: 'Nova', hp: 10, max_hp: 10, attack: 7, defense: 1, move: 5, max_move: 5, position: { x: 7, y: 6 }, _isActive: false },
                { id: 'e6', player_id: 'p3', name: 'Shard', hp: 0, max_hp: 12, attack: 3, defense: 6, move: 2, max_move: 2, position: { x: 9, y: 8 }, _isActive: false },
            ]
        },
        {
            id: 'p4',
            nickname: 'GhostUnit',
            team: 2,
            entities: [
                { id: 'e10', player_id: 'p4', name: 'Cinder', hp: 13, max_hp: 16, attack: 4, defense: 3, move: 4, max_move: 4, position: { x: 9, y: 3 }, _isActive: false },
                { id: 'e11', player_id: 'p4', name: 'Glitch', hp: 7, max_hp: 9, attack: 6, defense: 2, move: 3, max_move: 5, position: { x: 8, y: 2 }, _isActive: false },
                { id: 'e12', player_id: 'p4', name: 'Spike', hp: 10, max_hp: 13, attack: 5, defense: 4, move: 2, max_move: 3, position: { x: 7, y: 4 }, _isActive: false },
            ]
        }
    ],
});

// Generate 10x10 grid with some obstacles
function generateMockGrid(w, h) {
    const cells = [];
    const obstacles = [[3,3],[4,4],[5,5],[6,6],[3,7],[7,3],[5,2],[4,8],[6,1],[2,6]];
    for (let x = 0; x < w; x++) {
        cells[x] = [];
        for (let y = 0; y < h; y++) {
            cells[x][y] = {
                entity_id: '',
                obstacle: obstacles.some(o => o[0] === x && o[1] === y),
            };
        }
    }
    return { width: w, height: h, cells };
}

const mockGrid = ref(generateMockGrid(10, 10));

// All entities flat list
const allEntities = computed(() => {
    const all = [];
    mockPlayers.value.ally.forEach(p => all.push(...p.entities));
    mockPlayers.value.enemy.forEach(p => all.push(...p.entities));
    return all;
});

// Team colors mapping
const teamColors = ref({
    p1: '#00a8ff',  // Blue - current player
    p2: '#39ff13',  // Green - ally
    p3: '#ff2020',  // Red - enemy 1
    p4: '#b030ff',  // Purple - enemy 2
});

// Combat header computed values
const allyEntities = computed(() => {
    const ents = [];
    mockPlayers.value.ally.forEach(p => ents.push(...p.entities));
    return ents;
});

const enemyEntities = computed(() => {
    const ents = [];
    mockPlayers.value.enemy.forEach(p => ents.push(...p.entities));
    return ents;
});

const allyTeamHp = computed(() => allyEntities.value.reduce((sum, e) => sum + e.hp, 0));
const allyTeamMaxHp = computed(() => allyEntities.value.reduce((sum, e) => sum + e.max_hp, 0));
const allyCharsRemaining = computed(() => allyEntities.value.filter(e => e.hp > 0).length);

const enemyTeamHp = computed(() => enemyEntities.value.reduce((sum, e) => sum + e.hp, 0));
const enemyTeamMaxHp = computed(() => enemyEntities.value.reduce((sum, e) => sum + e.max_hp, 0));
const enemyCharsRemaining = computed(() => enemyEntities.value.filter(e => e.hp > 0).length);

// Initiative / turn order (sorted by delay)
const turnOrder = ref([
    { player_id: 'p1', entity_id: 'e1', delay: 0, name: 'Vex-7' },
    { player_id: 'p3', entity_id: 'e4', delay: 45, name: 'Rex-4' },
    { player_id: 'p2', entity_id: 'e7', delay: 60, name: 'Ash-3' },
    { player_id: 'p4', entity_id: 'e10', delay: 80, name: 'Cinder' },
    { player_id: 'p1', entity_id: 'e2', delay: 95, name: 'Kira' },
    { player_id: 'p3', entity_id: 'e5', delay: 110, name: 'Nova' },
    { player_id: 'p2', entity_id: 'e8', delay: 125, name: 'Flint' },
    { player_id: 'p4', entity_id: 'e11', delay: 140, name: 'Glitch' },
    { player_id: 'p1', entity_id: 'e3', delay: 155, name: 'Bolt-9' },
    { player_id: 'p2', entity_id: 'e9', delay: 170, name: 'Pulse' },
    { player_id: 'p4', entity_id: 'e12', delay: 200, name: 'Spike' },
]);

const currentEntityId = ref('e1');

// Mock highlighted cells (move range for active character)
const highlightedCells = ref([
    { x: 1, y: 1, type: 'move' },
    { x: 0, y: 2, type: 'move' },
    { x: 2, y: 2, type: 'move' },
    { x: 1, y: 3, type: 'move' },
    { x: 2, y: 1, type: 'move' },
    { x: 0, y: 1, type: 'move' },
    { x: 2, y: 3, type: 'attack' },
]);

// Timers
const matchSeconds = ref(272); // 4:32
const shotClock = ref(23);

const matchDuration = computed(() => {
    const m = Math.floor(matchSeconds.value / 60);
    const s = matchSeconds.value % 60;
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});

let matchTimer = null;
let shotTimer = null;

onMounted(() => {
    // Tick match timer
    matchTimer = setInterval(() => {
        matchSeconds.value++;
    }, 1000);

    // Tick shot clock
    shotTimer = setInterval(() => {
        if (shotClock.value > 0) {
            shotClock.value--;
        }
    }, 1000);
});

onUnmounted(() => {
    clearInterval(matchTimer);
    clearInterval(shotTimer);
});

function handleAction(type) {
    console.log('[BattleArena] Action:', type);
}
</script>

<template>
    <Head title="Battle Arena | Combat Engaged" />

    <TacticalLayout v-if="user" :user="user">
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
            />

            <!-- MAIN CONTENT: Rosters + Board -->
            <div class="arena__body">
                <!-- LEFT ROSTER: Allied Forces -->
                <TeamRosterPanel
                    :players="mockPlayers.ally"
                    :detailed-player-id="CURRENT_PLAYER_ID"
                    :team-colors="teamColors"
                    side="left"
                />

                <!-- CENTER: Board + Actions -->
                <div class="arena__center">
                    <IsoBoardGrid
                        :grid="mockGrid"
                        :entities="allEntities"
                        :current-entity-id="currentEntityId"
                        :team-colors="teamColors"
                        :highlighted-cells="highlightedCells"
                    />

                    <ActionPanel
                        :is-player-turn="true"
                        @action="handleAction"
                    />
                </div>

                <!-- RIGHT ROSTER: Hostile Forces -->
                <TeamRosterPanel
                    :players="mockPlayers.enemy"
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
</style>
