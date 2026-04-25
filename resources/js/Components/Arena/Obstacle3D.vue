<script setup>
import { computed } from 'vue';

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
</script>

<template>
    <TresMesh
        :position="position"
        cast-shadow
    >
        <TresBoxGeometry :args="[tileSize * 0.8, tileHeight * 2, tileSize * 0.8]" />
        <TresMeshStandardMaterial
            :color="effects ? '#2a2a2c' : '#3d2b1f'"
            :roughness="0.9"
            :metalness="0.2"
        />
    </TresMesh>
</template>
