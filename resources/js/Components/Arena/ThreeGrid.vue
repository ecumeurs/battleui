<!-- @spec-link [[ui_iso_board]] -->
<script setup>
import { computed, ref, onMounted } from 'vue';
import { TresCanvas } from '@tresjs/core';
import { OrbitControls } from '@tresjs/cientos';
import * as THREE from 'three';
import Tile3D from './Tile3D.vue';
import Obstacle3D from './Obstacle3D.vue';
import Pawn3D from './Pawn3D.vue';
import PostProcess from './PostProcess.vue';
import HighlightMaterial from './HighlightMaterial.vue';
import GridHighlight from './GridHighlight.vue';
import FacingIndicator3D from './FacingIndicator3D.vue';

const props = defineProps({
    grid: { type: Object, required: true },
    entities: { type: Array, default: () => [] },
    currentEntityId: { type: String, default: '' },
    teamColors: { type: Object, default: () => ({}) },
    highlightedCells: { type: Array, default: () => [] },
    autoRotate: { type: Boolean, default: false },
    effects: { type: Boolean, default: false },
});

const ready = ref(false);
function onCanvasReady(state) {
    ready.value = true;
    console.log('--- 3D Arena State ---');
    console.log('Scene:', state.scene);
    console.log('Renderer:', state.renderer);
    
    // Safety: convert children to array and handle potential proxying
    if (state.scene) {
        const scene = state.scene.value || state.scene;
        const children = scene.children ? Array.from(scene.children) : [];
        const lights = children.filter(c => c && c.isLight);
        console.log('Active Lights:', lights.length, lights.map(l => l.type));
    }
    console.log('Effects Mode:', props.effects ? 'ON' : 'OFF');
}

const emit = defineEmits(['tile-click']);

/*
 * Three.js coordinate system used throughout this component:
 *   X = grid.x column
 *   Y = terrain elevation (cell.height * TILE_HEIGHT)
 *   Z = grid.y row
 * Grid cells are stored width-major: props.grid.cells[x][y].
 */
const TILE_SIZE = 1.0;
const TILE_HEIGHT = 0.25;

/* Camera origin: grid center at floor level. */
const gridCenter = computed(() => ({
    x: (props.grid?.width ?? 1) * TILE_SIZE * 0.5,
    z: (props.grid?.height ?? 1) * TILE_SIZE * 0.5,
}));

const cameraPos = computed(() => {
    const size = Math.max(props.grid?.width ?? 10, props.grid?.height ?? 10);
    const dist = size * 0.9;
    return [gridCenter.value.x + dist, dist * 0.8, gridCenter.value.z + dist];
});

/*
 * Flatten grid.cells[x][y] into a renderable list. We avoid optimizing here
 * (InstancedMesh etc.) until we have profiling numbers — correctness first.
 */
const tiles = computed(() => {
    const out = [];
    const cells = props.grid?.cells;
    if (!cells) return out;
    for (let x = 0; x < props.grid.width; x++) {
        for (let y = 0; y < props.grid.height; y++) {
            const cell = cells[x]?.[y];
            if (!cell) continue;
            out.push({
                x,
                y,
                height: cell.height ?? 0,
                obstacle: !!cell.obstacle,
                entityId: cell.entity_id || '',
            });
        }
    }
    return out;
});

function tilePosition(tile) {
    // Center of filled column — bottom at y=0, top at (height+1)*TILE_HEIGHT.
    const blockH = (tile.height + 1) * TILE_HEIGHT;
    return [tile.x * TILE_SIZE, blockH / 2, tile.y * TILE_SIZE];
}

function tileColor(tile) {
    if (tile.obstacle) return '#3d2b1f';
    return '#4a4a4f'; // Worn Steel — ui_theme base color
}

function highlightAt(x, y) {
    return props.highlightedCells.find((c) => c.x === x && c.y === y);
}

function entityAt(x, y) {
    return props.entities.find(
        (e) => e.position?.x === x && e.position?.y === y && !e.dead && e.hp > 0,
    );
}

// ── Post-Processing Placeholder ──────────────────────────────────────────────
// Logic moved to PostProcess.vue

function surfaceHeight(x, y) {
    const cell = props.grid?.cells?.[x]?.[y];
    return cell?.height ?? 0;
}

function entityColor(entity) {
    if (!entity) return '#ffffff';
    if (entity.is_self) return '#39ff13';
    const byPlayer = props.teamColors?.[entity.player_id];
    if (byPlayer) return byPlayer;
    const byNickname = props.teamColors?.[entity.nickname];
    if (byNickname) return byNickname;
    return entity.team === 1 ? '#00a8ff' : '#ff2020';
}

function onTileClick(tile, event) {
    event?.stopPropagation?.();
    emit('tile-click', tile.x, tile.y);
}
</script>

<template>
    <div class="three-grid">
        <TresCanvas
            @ready="onCanvasReady"
            clear-color="#05050a"
            shadows
            :shadow-map-type="THREE.PCFShadowMap"
            alpha
            window-size
            power-preference="high-performance"
            :disable-render="effects"
        >
                <TresPerspectiveCamera make-default :position="cameraPos" :look-at="[gridCenter.x, 0, gridCenter.z]" :fov="45" />
                <OrbitControls
                    :target="[gridCenter.x, 0, gridCenter.z]"
                    :max-polar-angle="Math.PI / 2.2"
                    :min-distance="4"
                    :max-distance="60"
                    :enable-damping="true"
                    :auto-rotate="autoRotate"
                    :auto-rotate-speed="1.2"
                />

                <PostProcess :enabled="effects" />

                <TresAmbientLight :intensity="effects ? 0.6 : 0.8" :color="effects ? '#2a2a3a' : '#ffffff'" />
                <TresFogExp2 v-if="effects" color="#05050a" :density="0.015" />

                <!-- Warm overhead key light -->
                <TresPointLight
                    :position="[gridCenter.x, 5, gridCenter.z]"
                    color="#ffecb3"
                    :intensity="effects ? 50 : 150"
                    :distance="60"
                    :decay="1.5"
                />
                <template v-if="effects">
                    <TresSpotLight
                        :position="[gridCenter.x - 8, 12, gridCenter.z - 8]"
                        color="#00f2ff"
                        :intensity="800"
                        :distance="40"
                        :angle="Math.PI / 4"
                        :penumbra="0.3"
                        cast-shadow
                    />
                    <TresSpotLight
                        :position="[gridCenter.x + 8, 12, gridCenter.z + 8]"
                        color="#ff00ff"
                        :intensity="800"
                        :distance="40"
                        :angle="Math.PI / 4"
                        :penumbra="0.3"
                        cast-shadow
                    />
                    <!-- Specular rim light for copper shine -->
                    <TresSpotLight
                        :position="[gridCenter.x + 14, 7, gridCenter.z - 14]"
                        color="#ffccaa"
                        :intensity="250"
                        :distance="50"
                        :angle="Math.PI / 4"
                        :penumbra="0.5"
                    />
                </template>
                <TresDirectionalLight
                    v-else
                    :position="[gridCenter.x + 10, 18, gridCenter.z + 10]"
                    :intensity="2.0"
                    cast-shadow
                />

                <!-- Terrain tiles -->
                <Tile3D
                    v-for="tile in tiles"
                    :key="'tile-' + tile.x + '-' + tile.y"
                    :tile="tile"
                    :tile-size="TILE_SIZE"
                    :tile-height="TILE_HEIGHT"
                    :effects="effects"
                    @click="(e) => onTileClick(tile, e)"
                />

                <!-- Obstacles -->
                <Obstacle3D
                    v-for="tile in tiles.filter((t) => t.obstacle)"
                    :key="'obs-' + tile.x + '-' + tile.y"
                    :tile="tile"
                    :tile-size="TILE_SIZE"
                    :tile-height="TILE_HEIGHT"
                    :effects="effects"
                />

                <!-- Movement / attack highlights -->
                <GridHighlight
                    v-for="h in highlightedCells"
                    :key="'hl-' + h.x + '-' + h.y + '-' + h.type"
                    :x="h.x"
                    :y="h.y"
                    :type="h.type"
                    :tile-size="TILE_SIZE"
                    :tile-height="TILE_HEIGHT"
                    :surface-height="surfaceHeight(h.x, h.y)"
                />

                <!-- Pawns -->
                <Pawn3D
                    v-for="entity in entities.filter((e) => !e.dead && e.hp > 0)"
                    :key="'pawn-' + entity.id"
                    :entity="entity"
                    :color="entityColor(entity)"
                    :is-current="entity.id === currentEntityId"
                    :surface-height="surfaceHeight(entity.position.x, entity.position.y)"
                    :tile-size="TILE_SIZE"
                    :tile-height="TILE_HEIGHT"
                    :effects="effects"
                    :grid-ready="ready"
                />

                <!-- Facing indicators -->
                <FacingIndicator3D
                    v-for="entity in entities.filter((e) => !e.dead && e.hp > 0)"
                    :key="'facing-' + entity.id"
                    :facing="entity.facing"
                    :x="entity.position.x"
                    :y="entity.position.y"
                    :tile-size="TILE_SIZE"
                    :tile-height="TILE_HEIGHT"
                    :surface-height="surfaceHeight(entity.position.x, entity.position.y)"
                />
        </TresCanvas>
    </div>
</template>

<style scoped>
.three-grid {
    flex: 1;
    position: relative;
    min-height: 0;
    overflow: hidden;
    background: radial-gradient(circle at center, rgba(0, 242, 255, 0.04) 0%, transparent 60%);
}
</style>
