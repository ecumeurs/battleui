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

const position = computed(() => [
    props.tile.x * props.tileSize,
    props.tile.height * props.tileHeight + props.tileHeight * 1.5,
    props.tile.y * props.tileSize,
]);

const obstacleColor = computed(() => {
    const colors = [
        '#3d2b1f', // Deep brown
        '#4a3728', // Dark earth
        '#5c4033', // Bark
        '#7b3f00', // Chocolate
        '#8b4513', // Saddle brown
        '#a0522d', // Sienna
        '#b24412', // Rust
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
