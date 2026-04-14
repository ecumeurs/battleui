<!-- @spec-link [[ui_iso_board]] -->
<script setup>
import { computed, ref } from 'vue';
import CharacterPawn from './CharacterPawn.vue';
import HoloObstacle from './HoloObstacle.vue';

const props = defineProps({
    grid: { type: Object, required: true },
    entities: { type: Array, default: () => [] },
    currentEntityId: { type: String, default: '' },
    teamColors: { type: Object, default: () => ({}) },
    highlightedCells: { type: Array, default: () => [] },
});

const emit = defineEmits(['tile-click']);

/*
 * Isometric tile dimensions.
 * Standard 2:1 ratio: diamond is TILE_W wide and TILE_H tall.
 * TILE_H = TILE_W / 2 for correct isometric tessellation.
 */
const TILE_W = 64;
const TILE_H = 32;

/* Zoom & Pan state */
const zoom = ref(1.0);
const panX = ref(0);
const panY = ref(0);
const PAN_STEP = 40;
const ZOOM_MIN = 0.4;
const ZOOM_MAX = 2.0;
const ZOOM_STEP = 0.15;

function zoomIn() { zoom.value = Math.min(zoom.value + ZOOM_STEP, ZOOM_MAX); }
function zoomOut() { zoom.value = Math.max(zoom.value - ZOOM_STEP, ZOOM_MIN); }
function panUp() { panY.value -= PAN_STEP; }
function panDown() { panY.value += PAN_STEP; }
function panLeft() { panX.value -= PAN_STEP; }
function panRight() { panX.value += PAN_STEP; }
function resetView() { zoom.value = 1.0; panX.value = 0; panY.value = 0; }

/*
 * ISO projection: tile grid (col, row) -> screen pixel (px, py).
 * For a standard 2:1 isometric view:
 *   screenX = (col - row) * TILE_W / 2
 *   screenY = (col + row) * TILE_H / 2
 */
function tileToScreen(col, row) {
    return {
        px: (col - row) * (TILE_W / 2),
        py: (col + row) * (TILE_H / 2),
    };
}

/* Compute board pixel extents to center it */
const boardPixelW = computed(() => (props.grid.width + props.grid.height) * (TILE_W / 2));
const boardPixelH = computed(() => (props.grid.width + props.grid.height) * (TILE_H / 2));

/* Offset so (0,0) tile's left corner aligns to center-top of the container */
const originOffset = computed(() => ({
    x: (props.grid.height) * (TILE_W / 2),
    y: 10,
}));

/* Depth for obstacle extrusion (height of side faces) */
const DEPTH = 6;

function isCellHighlighted(col, row) {
    return props.highlightedCells.find(c => c.x === col && c.y === row);
}

function entityAt(col, row) {
    return props.entities.find(e => e.position.x === col && e.position.y === row && !e.dead);
}

function isObstacle(col, row) {
    if (!props.grid.cells || !props.grid.cells[col]) return false;
    return props.grid.cells[col][row]?.obstacle || false;
}

function entityShadeOffset(entity) {
    const members = props.entities.filter(e => e.team === entity.team && e.is_self === entity.is_self);
    return members.indexOf(entity);
}

function onWheel(e) {
    e.preventDefault();
    if (e.deltaY < 0) zoomIn();
    else zoomOut();
}
</script>

<template>
    <div class="iso-board-wrapper" @wheel.prevent="onWheel">
        <!-- Zoom/Pan Controls -->
        <div class="board-controls">
            <div class="board-controls__zoom">
                <button class="board-ctrl-btn" @click="zoomIn" title="Zoom In">+</button>
                <span class="board-controls__zoom-level">{{ Math.round(zoom * 100) }}%</span>
                <button class="board-ctrl-btn" @click="zoomOut" title="Zoom Out">−</button>
            </div>
            <div class="board-controls__pan">
                <button class="board-ctrl-btn board-ctrl-btn--up" @click="panUp" title="Pan Up">▲</button>
                <div class="board-controls__pan-row">
                    <button class="board-ctrl-btn" @click="panLeft" title="Pan Left">◄</button>
                    <button class="board-ctrl-btn board-ctrl-btn--center" @click="resetView" title="Reset">⊙</button>
                    <button class="board-ctrl-btn" @click="panRight" title="Pan Right">►</button>
                </div>
                <button class="board-ctrl-btn board-ctrl-btn--down" @click="panDown" title="Pan Down">▼</button>
            </div>
        </div>

        <!-- Board viewport -->
        <div class="iso-board-viewport">
            <div
                class="iso-board"
                :style="{
                    width: boardPixelW + TILE_W + 'px',
                    height: boardPixelH + DEPTH + 40 + 'px',
                    transform: `translate(${panX}px, ${panY}px) scale(${zoom})`,
                }"
            >
                <div class="iso-board__grid" :style="{
                    transform: `translateX(${originOffset.x}px) translateY(${originOffset.y}px)`,
                }">
                    <!-- Render tiles back-to-front for correct z-order -->
                    <template v-for="row in grid.height" :key="'row-' + row">
                        <template v-for="col in grid.width" :key="'tile-' + col + '-' + row">
                            <div
                                class="iso-tile"
                                :class="{
                                    'iso-tile--obstacle': isObstacle(col - 1, row - 1),
                                    'iso-tile--move': isCellHighlighted(col - 1, row - 1)?.type === 'move',
                                    'iso-tile--attack': isCellHighlighted(col - 1, row - 1)?.type === 'attack',
                                    'iso-tile--active': entityAt(col - 1, row - 1)?.id === currentEntityId,
                                }"
                                :style="{
                                    left: tileToScreen(col - 1, row - 1).px + 'px',
                                    top: tileToScreen(col - 1, row - 1).py + 'px',
                                    width: TILE_W + 'px',
                                    height: TILE_H + DEPTH + 'px',
                                    zIndex: (col - 1) + (row - 1),
                                }"
                                @click="emit('tile-click', col - 1, row - 1)"
                            >
                                <!-- Top face diamond (width x height/2:1 ratio) -->
                                <svg
                                    class="iso-tile__svg"
                                    :width="TILE_W"
                                    :height="TILE_H + DEPTH"
                                    :viewBox="`0 0 ${TILE_W} ${TILE_H + DEPTH}`"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <!-- Left depth face -->
                                    <polygon
                                        :points="`0,${TILE_H/2} ${TILE_W/2},${TILE_H} ${TILE_W/2},${TILE_H + DEPTH} 0,${TILE_H/2 + DEPTH}`"
                                        class="iso-tile__depth-l"
                                    />
                                    <!-- Right depth face -->
                                    <polygon
                                        :points="`${TILE_W},${TILE_H/2} ${TILE_W/2},${TILE_H} ${TILE_W/2},${TILE_H + DEPTH} ${TILE_W},${TILE_H/2 + DEPTH}`"
                                        class="iso-tile__depth-r"
                                    />
                                    <!-- Top diamond face (drawn last to be on top) -->
                                    <polygon
                                        :points="`${TILE_W/2},0 ${TILE_W},${TILE_H/2} ${TILE_W/2},${TILE_H} 0,${TILE_H/2}`"
                                        class="iso-tile__top"
                                    />
                                </svg>
                            </div>

                            <!-- Holo Obstacle at this position -->
                            <HoloObstacle
                                v-if="isObstacle(col - 1, row - 1)"
                                :seed="(col - 1) + (row - 1)"
                                :style="{
                                    left: (tileToScreen(col - 1, row - 1).px + TILE_W / 2) + 'px',
                                    top: (tileToScreen(col - 1, row - 1).py + TILE_H / 2) + 'px',
                                    zIndex: ((col - 1) + (row - 1)) * 10 + 2,
                                }"
                            />

                            <!-- Character pawn at this position -->
                            <CharacterPawn
                                v-if="entityAt(col - 1, row - 1)"
                                :entity="entityAt(col - 1, row - 1)"
                                :team-color="entityAt(col - 1, row - 1).is_self ? '#39ff13' : (props.teamColors[entityAt(col - 1, row - 1).team] || '#ff2020')"
                                :is-active="entityAt(col - 1, row - 1).id === currentEntityId"
                                :shade-offset="entityShadeOffset(entityAt(col - 1, row - 1))"
                                :style="{
                                    left: (tileToScreen(col - 1, row - 1).px + TILE_W / 2) + 'px',
                                    top: (tileToScreen(col - 1, row - 1).py) + 'px',
                                    zIndex: ((col - 1) + (row - 1)) * 10 + 5,
                                }"
                            />
                        </template>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.iso-board-wrapper {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    min-height: 0;
}

.iso-board-wrapper::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at center, rgba(0, 242, 255, 0.025) 0%, transparent 60%);
    pointer-events: none;
}

.iso-board-viewport {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.iso-board {
    position: relative;
    transform-origin: center center;
    transition: transform 0.2s ease;
}

.iso-board__grid {
    position: relative;
}

/* ─── CONTROLS ─── */
.board-controls {
    position: absolute;
    bottom: 12px;
    left: 12px;
    z-index: 200;
    display: flex;
    flex-direction: column;
    gap: 8px;
    background: rgba(10, 10, 11, 0.8);
    border: 1px solid rgba(0, 242, 255, 0.15);
    padding: 8px;
    backdrop-filter: blur(4px);
}

.board-controls__zoom {
    display: flex;
    align-items: center;
    gap: 4px;
    justify-content: center;
}

.board-controls__zoom-level {
    font-family: 'JetBrains Mono', monospace;
    font-size: 10px;
    color: #00f2ff;
    min-width: 36px;
    text-align: center;
}

.board-controls__pan {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.board-controls__pan-row {
    display: flex;
    gap: 2px;
}

.board-ctrl-btn {
    width: 24px;
    height: 24px;
    background: rgba(26, 26, 30, 0.9);
    border: 1px solid rgba(0, 242, 255, 0.2);
    color: #00f2ff;
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
    padding: 0;
}

.board-ctrl-btn:hover {
    background: rgba(0, 242, 255, 0.1);
    border-color: rgba(0, 242, 255, 0.5);
    box-shadow: 0 0 6px rgba(0, 242, 255, 0.3);
}

.board-ctrl-btn--center {
    color: #ff00ff;
    border-color: rgba(255, 0, 255, 0.2);
}

.board-ctrl-btn--center:hover {
    background: rgba(255, 0, 255, 0.1);
    border-color: rgba(255, 0, 255, 0.5);
}

/* ─── ISO TILE (single SVG with all three faces) ─── */
.iso-tile {
    position: absolute;
    cursor: pointer;
}

.iso-tile__svg {
    display: block;
}

/* Top face */
.iso-tile__top {
    fill: rgba(22, 22, 28, 0.9);
    stroke: rgba(0, 242, 255, 0.2);
    stroke-width: 1;
    transition: fill 0.2s, stroke 0.2s, stroke-width 0.2s;
}

.iso-tile:hover .iso-tile__top {
    fill: rgba(28, 28, 36, 0.95);
    stroke: rgba(0, 242, 255, 0.5);
    stroke-width: 1.5;
}

/* Left depth face */
.iso-tile__depth-l {
    fill: rgba(8, 8, 12, 0.9);
    stroke: rgba(0, 242, 255, 0.08);
    stroke-width: 0.5;
}

/* Right depth face */
.iso-tile__depth-r {
    fill: rgba(14, 14, 18, 0.9);
    stroke: rgba(0, 242, 255, 0.08);
    stroke-width: 0.5;
}

/* ─── OBSTACLE ─── */
.iso-tile--obstacle .iso-tile__top {
    fill: rgba(61, 43, 31, 0.65);
    stroke: rgba(61, 43, 31, 0.5);
}

.iso-tile--obstacle .iso-tile__depth-l {
    fill: rgba(35, 24, 16, 0.9);
    stroke: rgba(61, 43, 31, 0.25);
}

.iso-tile--obstacle .iso-tile__depth-r {
    fill: rgba(42, 29, 20, 0.9);
    stroke: rgba(61, 43, 31, 0.25);
}

/* ─── MOVE HIGHLIGHT ─── */
.iso-tile--move .iso-tile__top {
    fill: rgba(0, 242, 255, 0.12);
    stroke: rgba(0, 242, 255, 0.6);
    stroke-width: 1.5;
    filter: drop-shadow(0 0 4px rgba(0, 242, 255, 0.35));
}

.iso-tile--move .iso-tile__depth-l {
    fill: rgba(0, 80, 90, 0.4);
}

.iso-tile--move .iso-tile__depth-r {
    fill: rgba(0, 100, 110, 0.4);
}

/* ─── ATTACK HIGHLIGHT ─── */
.iso-tile--attack .iso-tile__top {
    fill: rgba(255, 0, 255, 0.12);
    stroke: rgba(255, 0, 255, 0.6);
    stroke-width: 1.5;
    filter: drop-shadow(0 0 4px rgba(255, 0, 255, 0.35));
    animation: attack-stroke-pulse 1.5s ease-in-out infinite;
}

.iso-tile--attack .iso-tile__depth-l {
    fill: rgba(80, 0, 80, 0.4);
}

.iso-tile--attack .iso-tile__depth-r {
    fill: rgba(100, 0, 100, 0.4);
}

@keyframes attack-stroke-pulse {
    0%, 100% { stroke: rgba(255, 0, 255, 0.6); }
    50% { stroke: rgba(255, 0, 255, 0.9); }
}

/* ─── ACTIVE TILE GLOW ─── */
.iso-tile--active .iso-tile__top {
    stroke: #39ff13;
    stroke-width: 2;
    fill: rgba(57, 255, 19, 0.08);
    filter: drop-shadow(0 0 8px rgba(57, 255, 19, 0.5));
    animation: active-tile-pulse 2s ease-in-out infinite;
}

@keyframes active-tile-pulse {
    0%, 100% { stroke: rgba(57, 255, 19, 0.4); filter: drop-shadow(0 0 4px rgba(57, 255, 19, 0.2)); }
    50% { stroke: rgba(57, 255, 19, 0.9); filter: drop-shadow(0 0 12px rgba(57, 255, 19, 0.7)); }
}
</style>
