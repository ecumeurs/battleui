<script setup>
import { computed } from 'vue';

const props = defineProps({
    tile: { type: Object, required: true },
    tileSize: { type: Number, default: 1.0 },
    tileHeight: { type: Number, default: 0.25 },
    effects: { type: Boolean, default: false },
});

const position = computed(() => {
    const blockH = (props.tile.height + 1) * props.tileHeight;
    return [props.tile.x * props.tileSize, blockH / 2, props.tile.y * props.tileSize];
});

const color = computed(() => {
    if (props.effects) return '#141416'; // Gritty Industrial Floor
    if (props.tile.obstacle) return '#3d2b1f';
    return '#4a4a4f';
});
</script>

<template>
    <TresMesh
        :position="position"
        receive-shadow
        :user-data="{ gx: tile.x, gy: tile.y }"
    >
        <TresBoxGeometry :args="[tileSize * 0.98, (tile.height + 1) * tileHeight, tileSize * 0.98]" />
        <TresMeshStandardMaterial
            :color="color"
            :emissive="color"
            :emissive-intensity="0.6"
            :roughness="0.7"
            :metalness="0.2"
        />
    </TresMesh>
</template>
