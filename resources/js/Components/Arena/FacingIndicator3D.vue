<script setup>
// @spec-link [[ui_iso_board]]
import * as THREE from 'three';

const props = defineProps({
    facing: { type: String, default: 'Up' },
    x: { type: Number, required: true },
    y: { type: Number, required: true },
    tileSize: { type: Number, default: 1.0 },
    tileHeight: { type: Number, default: 0.25 },
    surfaceHeight: { type: Number, default: 0 },
});

const FACING_ROTATIONS = {
    Up:    [Math.PI / 2, 0, 0],
    Down:  [-Math.PI / 2, 0, Math.PI],
    Right: [Math.PI / 2, 0, -Math.PI / 2],
    Left:  [Math.PI / 2, 0, Math.PI / 2],
};

function facingRotation(facing) {
    return FACING_ROTATIONS[facing] ?? FACING_ROTATIONS['Up'];
}
</script>

<template>
    <TresMesh
        :position="[
            x * tileSize,
            surfaceHeight * tileHeight + tileHeight + 0.03,
            y * tileSize,
        ]"
        :rotation="facingRotation(facing)"
    >
        <TresConeGeometry :args="[0.15, 0.5, 3]" />
        <TresMeshBasicMaterial color="#1a5c1a" />
    </TresMesh>
</template>
