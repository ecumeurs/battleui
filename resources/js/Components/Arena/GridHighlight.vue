<script setup>
// @spec-link [[ui_selection_highlight]]
import { computed } from 'vue';
import HighlightMaterial from './HighlightMaterial.vue';

const props = defineProps({
    x: { type: Number, required: true },
    y: { type: Number, required: true },
    type: { type: String, default: 'move' },
    color: { type: String },
    tileSize: { type: Number, default: 1.0 },
    tileHeight: { type: Number, default: 0.25 },
    surfaceHeight: { type: Number, default: 0 },
});

const TYPE_COLORS = { attack: '#ff00ff', skill: '#fbbf24', move: '#00f2ff' };
const defaultColor = computed(() => TYPE_COLORS[props.type] ?? '#00f2ff');
</script>

<template>
    <TresMesh
        :position="[
            (x + 0.5) * tileSize,
            surfaceHeight * tileHeight + tileHeight + 0.02,
            (y + 0.5) * tileSize,
        ]"
        :rotation="[-Math.PI / 2, 0, 0]"
    >
        <TresPlaneGeometry :args="[tileSize * 0.95, tileSize * 0.95]" />
        <HighlightMaterial
            :color="color || defaultColor"
            :type="type"
        />

    </TresMesh>
</template>
