<script setup>
// @spec-link [[ui_selection_highlight]]
import { computed } from 'vue';
import * as THREE from 'three';
import { ZONE_COLORS } from '@/constants/theme.js';

const props = defineProps({
    x: { type: Number, required: true },
    y: { type: Number, required: true },
    type: { type: String, default: 'move' },
    tileSize: { type: Number, default: 1.0 },
    tileHeight: { type: Number, default: 0.25 },
    surfaceHeight: { type: Number, default: 0 },
});

const color = computed(() => ZONE_COLORS[props.type] ?? ZONE_COLORS.move);

const position = computed(() => [
    (props.x + 0.5) * props.tileSize,
    (props.surfaceHeight + 1) * props.tileHeight + 0.08,
    (props.y + 0.5) * props.tileSize,
]);
</script>

<template>
    <TresMesh :position="position" :rotation="[-Math.PI / 2, 0, 0]">
        <TresCircleGeometry :args="[tileSize * 0.38, 24]" />
        <TresMeshBasicMaterial
            :color="color"
            :transparent="true"
            :opacity="0.75"
            :depth-test="false"
            :depth-write="false"
            :blending="THREE.AdditiveBlending"
        />
    </TresMesh>
</template>
