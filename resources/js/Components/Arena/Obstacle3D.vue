<script setup>
// @spec-link [[ui_holo_obstacle]]
// @spec-link [[mech_hologram_shader]]
import { computed } from 'vue';
import HologramMaterial from './HologramMaterial.vue';

const props = defineProps({
    tile: { type: Object, required: true },
    tileSize: { type: Number, default: 1.0 },
    tileHeight: { type: Number, default: 0.25 },
    effects: { type: Boolean, default: false },
});

const position = computed(() => {
    const surface = (props.tile.height + 1) * props.tileHeight;
    const obstacleH = props.tileHeight * 2.0;
    return [
        (props.tile.x + 0.5) * props.tileSize,
        surface + (obstacleH / 2) + 0.05, // 0.05 safety margin above surface
        (props.tile.y + 0.5) * props.tileSize,
    ];
});

const obstacleColor = computed(() => {
    const colors = [
        '#5d3b2f', // Warm Copper
        '#6a4738', // Oxidized Iron
        '#7c5043', // Terracotta Metal
        '#8b5a2b', // Golden Rust
        '#a67c52', // Brass
        '#b24412', // Industrial Orange
    ];
    // Simple stable hash based on coordinates
    const hash = (props.tile.x * 7 + props.tile.y * 13) % colors.length;
    return colors[Math.abs(hash)];
});
</script>

<template>
    <TresMesh
        :position="position"
        cast-shadow
    >
        <TresBoxGeometry :args="[tileSize * 0.8, tileHeight * 2, tileSize * 0.8]" />
        <HologramMaterial
            v-if="effects"
            :color="obstacleColor"
            :opacity="0.8"
        />
        <TresMeshStandardMaterial
            v-else
            :color="obstacleColor"
            :roughness="0.9"
            :metalness="0.2"
        />
    </TresMesh>
</template>
