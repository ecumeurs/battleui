<script setup>
// @spec-link [[ui_character_pawn]]
// @spec-link [[mech_hologram_shader]]
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { Html } from '@tresjs/cientos';
import * as THREE from 'three';
import HologramMaterial from './HologramMaterial.vue';

const props = defineProps({
    entity: { type: Object, required: true },
    color: { type: String, default: '#ffffff' },
    isCurrent: { type: Boolean, default: false },
    surfaceHeight: { type: Number, default: 0 },
    tileSize: { type: Number, default: 1.0 },
    tileHeight: { type: Number, default: 0.25 },
    effects: { type: Boolean, default: false },
    gridReady: { type: Boolean, default: false },
});

const mounted = ref(false);
const meshReady = ref(false);

onMounted(() => {
    mounted.value = true;
});

function onMeshReady() {
    meshReady.value = true;
}

const showOverlay = computed(() => mounted.value && props.gridReady);

const position = computed(() => {
    if (!props.entity?.position) return [0, 0, 0];
    const surface = (props.surfaceHeight + 1) * props.tileHeight;
    const pawnH = 0.8;
    return [
        (props.entity.position.x + 0.5) * props.tileSize,
        surface + (pawnH / 2) + 0.02, // 0.02 safety margin above surface
        (props.entity.position.y + 0.5) * props.tileSize,
    ];
});

const hpPct = computed(() => {
    if (!props.entity.max_hp) return 0;
    return Math.max(0, Math.min(100, Math.round((props.entity.hp / props.entity.max_hp) * 100)));
});
</script>

<template>
    <TresGroup :position="position">
        <!-- Pawn Mesh -->
        <TresMesh cast-shadow @ready="onMeshReady">
            <TresConeGeometry :args="[0.3, 0.8, 6]" />
            <HologramMaterial
                v-if="effects"
                :color="color"
                :opacity="isCurrent ? 0.8 : 0.65"
                :glow-intensity="isCurrent ? 0.8 : 1.4"
            />

            <TresMeshStandardMaterial
                v-else
                :color="color"
                :emissive="color"
                :emissive-intensity="isCurrent ? 0.8 : 0.2"
                :roughness="0.35"
                :metalness="0.75"
            />
        </TresMesh>

        <!-- UI Overlay -->
        <Html v-if="showOverlay && entity" center :position="[0, 0.8, 0]" :distance-factor="6">
            <div class="pawn-overlay">
                <div class="pawn-overlay__name">{{ entity.nickname || entity.name }}</div>
                <div class="pawn-overlay__hp-bar">
                    <div
                        class="pawn-overlay__hp-fill"
                        :style="{
                            width: hpPct + '%',
                            backgroundColor: color,
                            boxShadow: `0 0 4px ${color}`
                        }"
                    ></div>
                </div>
            </div>
        </Html>
    </TresGroup>
</template>

<style scoped>
.pawn-overlay {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    pointer-events: none;
    user-select: none;
    z-index: 1000;
    filter: drop-shadow(0 0 2px rgba(0, 0, 0, 0.5));
}

.pawn-overlay__name {
    font-family: 'JetBrains Mono', monospace;
    font-size: 10px;
    font-weight: 800;
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: rgba(10, 10, 12, 0.7);
    padding: 1px 4px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    white-space: nowrap;
}

.pawn-overlay__hp-bar {
    width: 32px;
    height: 3px;
    background: rgba(5, 5, 5, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1px;
    overflow: hidden;
}

.pawn-overlay__hp-fill {
    height: 100%;
    transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
