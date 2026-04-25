<!-- @spec-link [[mech_frontend_test_seams]] -->
<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted, nextTick, computed } from 'vue';
import ThreeGrid from '@/Components/Arena/ThreeGrid.vue';

const props = defineProps({
    name: { type: String, required: true },
});

// ── grid factory ─────────────────────────────────────────────────────────────

function makeGrid(w, h, { obstacles = [], elevations = {} } = {}) {
    const cells = [];
    for (let x = 0; x < w; x++) {
        cells[x] = [];
        for (let y = 0; y < h; y++) {
            cells[x][y] = {
                height: elevations[`${x},${y}`] ?? 0,
                obstacle: obstacles.some(([ox, oy]) => ox === x && oy === y),
            };
        }
    }
    return { width: w, height: h, cells };
}

function ent(id, x, y, { team = 1, playerId = 'p1', nickname = 'UNIT', isSelf = false, hp = 20, facing = null } = {}) {
    return { id, position: { x, y }, hp, dead: false, team, player_id: playerId, nickname, is_self: isSelf, facing };
}

// ── fixture registry ──────────────────────────────────────────────────────────
// Each entry: { label, grid, entities, currentEntityId, teamColors, highlightedCells }

const FIXTURES = {
    'grid-flat': {
        label: 'Grid: Flat 5×5',
        grid: makeGrid(5, 5),
        entities: [],
        currentEntityId: '',
        teamColors: {},
        highlightedCells: [],
    },

    'grid-elevated': {
        label: 'Grid: Elevated Terrain',
        grid: makeGrid(5, 5, {
            elevations: {
                '0,0': 0, '1,0': 1, '2,0': 2, '3,0': 1, '4,0': 0,
                '0,1': 1, '1,1': 2, '2,1': 3, '3,1': 2, '4,1': 1,
                '0,2': 0, '1,2': 1, '2,2': 2, '3,2': 1, '4,2': 0,
                '0,3': 0, '1,3': 0, '2,3': 1, '3,3': 0, '4,3': 0,
                '0,4': 0, '1,4': 0, '2,4': 0, '3,4': 0, '4,4': 0,
            },
        }),
        entities: [],
        currentEntityId: '',
        teamColors: {},
        highlightedCells: [],
    },

    'grid-obstacles': {
        label: 'Grid: Obstacles',
        grid: makeGrid(5, 5, {
            obstacles: [[1, 1], [1, 3], [3, 1], [3, 3]],
            elevations: { '0,0': 1, '4,4': 1 },
        }),
        entities: [],
        currentEntityId: '',
        teamColors: {},
        highlightedCells: [],
    },

    'grid-pawn-self': {
        label: 'Pawn: Self (Green)',
        grid: makeGrid(3, 3),
        entities: [ent('e1', 1, 1, { isSelf: true, nickname: 'GHOST' })],
        currentEntityId: '',
        teamColors: { p1: '#39ff13' },
        highlightedCells: [],
    },

    'grid-pawn-blue': {
        label: 'Pawn: Ally (Blue)',
        grid: makeGrid(3, 3),
        entities: [ent('e1', 1, 1, { team: 1, playerId: 'p1', nickname: 'ALPHA' })],
        currentEntityId: '',
        teamColors: { p1: '#00a8ff' },
        highlightedCells: [],
    },

    'grid-pawn-red': {
        label: 'Pawn: Enemy (Red)',
        grid: makeGrid(3, 3),
        entities: [ent('e1', 1, 1, { team: 2, playerId: 'p2', nickname: 'HOSTILE' })],
        currentEntityId: '',
        teamColors: { p2: '#ff2020' },
        highlightedCells: [],
    },

    'grid-pawn-active': {
        label: 'Pawn: Active Turn',
        grid: makeGrid(3, 3),
        entities: [ent('e_act', 1, 1, { isSelf: true, nickname: 'ACTIVE', playerId: 'p1' })],
        currentEntityId: 'e_act',
        teamColors: { p1: '#39ff13' },
        highlightedCells: [],
    },

    'grid-highlight-move': {
        label: 'Highlight: Move Range',
        grid: makeGrid(5, 5),
        entities: [ent('e1', 2, 2, { isSelf: true, nickname: 'MOVER', playerId: 'p1' })],
        currentEntityId: 'e1',
        teamColors: { p1: '#39ff13' },
        highlightedCells: [
            { x: 1, y: 2, type: 'move' }, { x: 3, y: 2, type: 'move' },
            { x: 2, y: 1, type: 'move' }, { x: 2, y: 3, type: 'move' },
            { x: 0, y: 2, type: 'move' }, { x: 4, y: 2, type: 'move' },
            { x: 1, y: 1, type: 'move' }, { x: 3, y: 1, type: 'move' },
            { x: 1, y: 3, type: 'move' }, { x: 3, y: 3, type: 'move' },
        ],
    },

    'grid-highlight-attack': {
        label: 'Highlight: Attack Range',
        grid: makeGrid(5, 5),
        entities: [
            ent('e1', 2, 2, { isSelf: true, nickname: 'ATTACKER', playerId: 'p1' }),
            ent('e2', 2, 1, { team: 2, playerId: 'p2', nickname: 'TARGET-N' }),
            ent('e3', 2, 3, { team: 2, playerId: 'p2', nickname: 'TARGET-S' }),
        ],
        currentEntityId: 'e1',
        teamColors: { p1: '#39ff13', p2: '#ff2020' },
        highlightedCells: [
            { x: 2, y: 1, type: 'attack' },
            { x: 2, y: 3, type: 'attack' },
        ],
    },

    'grid-facing': {
        label: 'Pawn: Facing Indicator',
        grid: makeGrid(5, 5),
        entities: [
            ent('e-up',    2, 0, { isSelf: true, nickname: 'NORTH', playerId: 'p1', facing: 'Up'    }),
            ent('e-right', 4, 2, { team: 1,      nickname: 'EAST',  playerId: 'p2', facing: 'Right' }),
            ent('e-down',  2, 4, { team: 2,      nickname: 'SOUTH', playerId: 'p3', facing: 'Down'  }),
            ent('e-left',  0, 2, { team: 1,      nickname: 'WEST',  playerId: 'p4', facing: 'Left'  }),
        ],
        currentEntityId: '',
        teamColors: { p1: '#39ff13', p2: '#00a8ff', p3: '#ff2020', p4: '#00a8ff' },
        highlightedCells: [],
    },

    'grid-full': {
        label: 'Grid: Full Scene',
        grid: makeGrid(6, 6, {
            obstacles: [[0, 0], [5, 5], [2, 1], [3, 4]],
            elevations: {
                '1,0': 1, '2,0': 2, '0,2': 1, '5,1': 2,
                '4,3': 1, '1,5': 1, '3,2': 2,
            },
        }),
        entities: [
            ent('e1', 1, 2, { isSelf: true, nickname: 'HERO', playerId: 'p1' }),
            ent('e2', 4, 1, { team: 2, playerId: 'p2', nickname: 'FOE-A' }),
            ent('e3', 3, 5, { team: 2, playerId: 'p2', nickname: 'FOE-B' }),
        ],
        currentEntityId: 'e1',
        teamColors: { p1: '#00a8ff', p2: '#ff2020' },
        highlightedCells: [
            { x: 1, y: 1, type: 'move' },
            { x: 2, y: 2, type: 'move' },
            { x: 0, y: 2, type: 'move' },
            { x: 1, y: 3, type: 'move' },
            { x: 4, y: 1, type: 'attack' },
        ],
    },
};

const fixture = FIXTURES[props.name] ?? null;

// Visual mode: ?visual=1 enables auto-rotate and skips the frozen flag.
// These pages are for manual review only — not snapshotted by Playwright.
const isVisual = computed(() => new URLSearchParams(window.location.search).get('visual') === '1');

// ── debug seam ────────────────────────────────────────────────────────────────
// Playwright Layer 2 waits for window.__upsilonDebug?.frozen === true.
// Visual mode intentionally omits frozen so Playwright ignores these pages.

onMounted(() => {
    nextTick(() => {
        window.__upsilonDebug = {
            ready: true,
            frozen: !isVisual.value,
            version: 1,
            fixture: props.name,
            visual: isVisual.value,
        };
    });
});
</script>

<template>
    <Head :title="`[FIXTURE] ${props.name}`" />

    <div class="viewer">
        <!-- Top bar -->
        <div class="viewer__bar">
            <a href="/__test/component" class="viewer__back">← ISOLATION TERMINAL</a>
            <span class="viewer__label">{{ fixture ? fixture.label : props.name }}</span>
            <span v-if="isVisual" class="viewer__visual-badge">VISUAL</span>
            <span class="viewer__name">{{ props.name }}</span>
        </div>

        <!-- 3D canvas -->
        <div v-if="fixture" class="viewer__canvas">
            <ThreeGrid
                :grid="fixture.grid"
                :entities="fixture.entities"
                :current-entity-id="fixture.currentEntityId"
                :team-colors="fixture.teamColors"
                :highlighted-cells="fixture.highlightedCells"
                :auto-rotate="isVisual"
                :effects="isVisual"
                @tile-click="() => {}"
            />
        </div>

        <!-- Unknown fixture error panel -->
        <div v-else class="viewer__error">
            <div class="viewer__error-content">
                <div class="viewer__error-badge">FIXTURE NOT FOUND</div>
                <h2 class="viewer__error-title">UNKNOWN ISOLATION TARGET</h2>
                <p class="viewer__error-msg">
                    No fixture registered for <code>{{ props.name }}</code>.<br />
                    Register it in <code>TestComponent.vue → FIXTURES</code>.
                </p>
                <a href="/__test/component" class="viewer__error-back">← RETURN TO TERMINAL</a>
            </div>
        </div>
    </div>
</template>

<style scoped>
.viewer {
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: #05050a;
    overflow: hidden;
}

.viewer__bar {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 10px 20px;
    background: rgba(10, 10, 11, 0.9);
    border-bottom: 1px solid rgba(0, 242, 255, 0.15);
    flex-shrink: 0;
    font-family: 'JetBrains Mono', monospace;
    z-index: 10;
}

.viewer__back {
    font-size: 10px;
    letter-spacing: 0.2em;
    color: rgba(0, 242, 255, 0.4);
    text-decoration: none;
    text-transform: uppercase;
    transition: color 0.15s;
    white-space: nowrap;
}

.viewer__back:hover {
    color: #00f2ff;
}

.viewer__label {
    font-family: 'Orbitron', sans-serif;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.1em;
    color: #00f2ff;
    text-transform: uppercase;
    flex: 1;
}

.viewer__visual-badge {
    font-size: 9px;
    letter-spacing: 0.35em;
    color: #ff00ff;
    border: 1px solid rgba(255, 0, 255, 0.4);
    padding: 1px 6px;
    text-transform: uppercase;
}

.viewer__name {
    font-size: 10px;
    letter-spacing: 0.25em;
    color: rgba(0, 242, 255, 0.25);
    text-transform: uppercase;
}

.viewer__canvas {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
}

/* ── error panel ── */
.viewer__error {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.viewer__error-content {
    background: rgba(22, 22, 28, 0.95);
    border: 1px solid rgba(255, 32, 32, 0.4);
    padding: 60px;
    text-align: center;
    box-shadow: 0 0 50px rgba(255, 0, 0, 0.08);
    max-width: 560px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    align-items: center;
}

.viewer__error-badge {
    font-family: 'JetBrains Mono', monospace;
    font-size: 10px;
    letter-spacing: 0.4em;
    color: #ff2020;
    border: 1px solid rgba(255, 32, 32, 0.4);
    padding: 3px 10px;
}

.viewer__error-title {
    font-family: 'Orbitron', sans-serif;
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 0.15em;
    color: #ff2020;
    text-shadow: 0 0 10px rgba(255, 32, 32, 0.4);
    margin: 0;
}

.viewer__error-msg {
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    color: rgba(224, 224, 224, 0.6);
    line-height: 1.7;
    margin: 0;
}

.viewer__error-msg code {
    color: rgba(0, 242, 255, 0.7);
    background: rgba(0, 242, 255, 0.05);
    padding: 1px 5px;
}

.viewer__error-back {
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    letter-spacing: 0.2em;
    color: rgba(224, 224, 224, 0.35);
    text-decoration: none;
    text-transform: uppercase;
    transition: color 0.15s;
    margin-top: 8px;
}

.viewer__error-back:hover {
    color: #00f2ff;
}
</style>
