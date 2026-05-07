<script setup>
// @spec-link [[ui_selection_highlight]]
import { shallowRef, watchEffect, onUnmounted } from 'vue';
import * as THREE from 'three';
import { ZONE_COLORS } from '@/constants/theme.js';

const props = defineProps({
    cells: { type: Array, default: () => [] },   // [{ x, y, type }]
    type: { type: String, default: 'move' },
    tileSize: { type: Number, default: 1.0 },
    tileHeight: { type: Number, default: 0.25 },
    grid: { type: Object, default: null },
});
// Half-width of each neon border strip in world units
const EDGE_HALF = 0.045;

const meshObject = shallowRef(null);

function getSurface(x, y) {
    const h = props.grid?.cells?.[x]?.[y]?.height ?? 0;
    return (h + 1) * props.tileHeight + 0.06;
}

watchEffect(() => {
    if (!props.cells.length) {
        meshObject.value = null;
        return;
    }

    const cellSet = new Set(props.cells.map(c => `${c.x},${c.y}`));
    const T = props.tileSize;
    const H = EDGE_HALF;
    const positions = [];
    const indices = [];
    let vi = 0;

    // Add a horizontal quad (flat on the XZ plane at height yW)
    function quad(ax, ay, az, bx, by, bz, cx, cy, cz, dx, dy, dz) {
        positions.push(ax, ay, az, bx, by, bz, cx, cy, cz, dx, dy, dz);
        indices.push(vi, vi + 1, vi + 2, vi, vi + 2, vi + 3);
        vi += 4;
    }

    for (const cell of props.cells) {
        const x = cell.x, y = cell.y;
        const yW = getSurface(x, y);

        // North border: neighbor (x, y-1) outside zone → edge at z = y * T
        if (!cellSet.has(`${x},${y - 1}`)) {
            const z = y * T;
            quad(x * T, yW, z - H,  (x + 1) * T, yW, z - H,
                 (x + 1) * T, yW, z + H,  x * T, yW, z + H);
        }
        // South border: neighbor (x, y+1) outside zone → edge at z = (y+1) * T
        if (!cellSet.has(`${x},${y + 1}`)) {
            const z = (y + 1) * T;
            quad(x * T, yW, z - H,  (x + 1) * T, yW, z - H,
                 (x + 1) * T, yW, z + H,  x * T, yW, z + H);
        }
        // West border: neighbor (x-1, y) outside zone → edge at xp = x * T
        if (!cellSet.has(`${x - 1},${y}`)) {
            const xp = x * T;
            quad(xp - H, yW, y * T,  xp + H, yW, y * T,
                 xp + H, yW, (y + 1) * T,  xp - H, yW, (y + 1) * T);
        }
        // East border: neighbor (x+1, y) outside zone → edge at xp = (x+1) * T
        if (!cellSet.has(`${x + 1},${y}`)) {
            const xp = (x + 1) * T;
            quad(xp - H, yW, y * T,  xp + H, yW, y * T,
                 xp + H, yW, (y + 1) * T,  xp - H, yW, (y + 1) * T);
        }
    }

    if (positions.length === 0) {
        meshObject.value = null;
        return;
    }

    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
    geometry.setIndex(indices);

    const color = ZONE_COLORS[props.type] ?? ZONE_COLORS.move;
    const material = new THREE.MeshBasicMaterial({
        color,
        side: THREE.DoubleSide,
        transparent: true,
        opacity: 0.9,
        depthTest: false,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
    });

    meshObject.value = new THREE.Mesh(geometry, material);
});

onUnmounted(() => {
    if (meshObject.value) {
        meshObject.value.geometry?.dispose();
        meshObject.value.material?.dispose();
    }
});
</script>

<template>
    <primitive v-if="meshObject" :object="meshObject" />
</template>
