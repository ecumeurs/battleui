<script setup>
// @spec-link [[ui_character_pawn]]
// @spec-link [[mech_hologram_shader]]
import { ref, shallowRef, computed, watch, onMounted } from 'vue';
import { useLoop } from '@tresjs/core';
import { Html } from '@tresjs/cientos';
import * as THREE from 'three';
import HologramMaterial from './HologramMaterial.vue';

const props = defineProps({
    entity: { type: Object, required: true },
    color: { type: String, default: '#ffffff' },
    isCurrent: { type: Boolean, default: false },
    isTargeted: { type: Boolean, default: false },
    surfaceHeight: { type: Number, default: 0 },
    tileSize: { type: Number, default: 1.0 },
    tileHeight: { type: Number, default: 0.25 },
    effects: { type: Boolean, default: false },
    gridReady: { type: Boolean, default: false },
});

const mounted = ref(false);
const meshReady = ref(false);

onMounted(() => { mounted.value = true; });
function onMeshReady() { meshReady.value = true; }

const showOverlay = computed(() => mounted.value && props.gridReady);

// ─── Movement tween ───────────────────────────────────────────────────────────
const groupRef = shallowRef(null);
const _targetPos = new THREE.Vector3();
const _lerpSpeed = 8; // units/sec multiplier; covers 1 tile (~1 unit) in ~125ms
let _initialized = false;

function _syncTargetPos() {
    if (!props.entity?.position) return;
    const surface = (props.surfaceHeight + 1) * props.tileHeight;
    _targetPos.set(
        (props.entity.position.x + 0.5) * props.tileSize,
        surface + 0.4 + 0.02,
        (props.entity.position.y + 0.5) * props.tileSize,
    );
}

_syncTargetPos(); // Set on script setup so first frame is close

watch(() => [props.entity?.position?.x, props.entity?.position?.y, props.surfaceHeight], () => {
    _syncTargetPos();
}, { deep: false });

// Flash animation state for attack/skill hit
const _flashTime = ref(0); // counts down from 0.6s to 0

const { onRender } = useLoop();
onRender(({ delta }) => {
    if (!groupRef.value) return;

    // Snap to correct position on first frame
    if (!_initialized) {
        groupRef.value.position.copy(_targetPos);
        _initialized = true;
        return;
    }

    // Lerp toward target
    const dist = groupRef.value.position.distanceTo(_targetPos);
    if (dist > 0.005) {
        groupRef.value.position.lerp(_targetPos, Math.min(1, delta * _lerpSpeed));
    } else {
        groupRef.value.position.copy(_targetPos);
    }

    // Flash timer countdown
    if (_flashTime.value > 0) {
        _flashTime.value = Math.max(0, _flashTime.value - delta);
    }
});

// Trigger flash when isTargeted activates
watch(() => props.isTargeted, (v) => {
    if (v) _flashTime.value = 0.6;
});

// Emissive intensity: base + flash pulse
const emissiveIntensity = computed(() => {
    const base = props.isCurrent ? 0.8 : 0.2;
    if (_flashTime.value > 0) {
        const pulse = Math.sin((_flashTime.value / 0.6) * Math.PI);
        return base + pulse * 2.5;
    }
    return base;
});

const hpPct = computed(() => {
    if (!props.entity.max_hp) return 0;
    return Math.max(0, Math.min(100, Math.round((props.entity.hp / props.entity.max_hp) * 100)));
});
</script>

<template>
    <TresGroup ref="groupRef">
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
                :emissive-intensity="emissiveIntensity"
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
    font-size: var(--fs-xs);
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
