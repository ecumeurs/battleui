<!-- @spec-link [[mech_frontend_test_seams]] -->
<!-- Debug-only component. Must sit inside <TresCanvas> to access camera context via useTresContext().
     When window.__upsilonDebug exists, populates __upsilonDebug.board with:
       .highlightedCells  — current zone array
       .hoveredCell       — cell currently under mouse, or null
       .getCellScreenPos(x, y) → { x, y } page coords (for page.mouse.move in Playwright)
       .getHighlightedCellScreenPositions() → cells with .screen attached
-->
<script setup>
import { watchEffect, onUnmounted } from 'vue';
import { useTresContext } from '@tresjs/core';
import * as THREE from 'three';

const props = defineProps({
    highlightedCells: { type: Array,  default: () => [] },
    hoveredCell:      { type: Object, default: null },
    tileSize:         { type: Number, default: 1.0 },
    tileHeight:       { type: Number, default: 0.25 },
});

const { camera, renderer } = useTresContext();

function getCellScreenPos(x, y) {
    const cam = camera.activeCamera.value;
    const rend = renderer.instance;
    if (!cam || !rend) return null;
    const worldPos = new THREE.Vector3(
        (x + 0.5) * props.tileSize,
        props.tileHeight,
        (y + 0.5) * props.tileSize,
    );
    worldPos.project(cam);
    const rect = rend.domElement.getBoundingClientRect();
    return {
        x: Math.round((worldPos.x + 1) / 2 * rect.width  + rect.left),
        y: Math.round((-worldPos.y + 1) / 2 * rect.height + rect.top),
    };
}

watchEffect(() => {
    const debug = window.__upsilonDebug;
    if (!debug) return;
    // Re-runs whenever highlightedCells or hoveredCell props change.
    debug.board = {
        highlightedCells: props.highlightedCells,
        hoveredCell:      props.hoveredCell,
        getCellScreenPos,
        getHighlightedCellScreenPositions: () =>
            props.highlightedCells.map(c => ({ ...c, screen: getCellScreenPos(c.x, c.y) })),
    };
});

onUnmounted(() => {
    if (window.__upsilonDebug) delete window.__upsilonDebug.board;
});
</script>
<template></template>
