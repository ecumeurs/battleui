<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Html } from '@tresjs/cientos';
import { useLoop } from '@tresjs/core';
import * as THREE from 'three';

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
watch(() => props.gridReady, (newVal) => {
    if (newVal) {
        setTimeout(() => {
            mounted.value = true;
        }, 200);
    }
}, { immediate: true });

const position = computed(() => [
    props.entity.position.x * props.tileSize,
    props.surfaceHeight * props.tileHeight + props.tileHeight / 2 + 0.4,
    props.entity.position.y * props.tileSize,
]);

const hpPct = computed(() => {
    if (!props.entity.max_hp) return 0;
    return Math.max(0, Math.min(100, Math.round((props.entity.hp / props.entity.max_hp) * 100)));
});

// ── Hologram Shader Logic ──────────────────────────────────────────────────
const { onRender } = useLoop();
const uTime = ref(0);
onRender(({ elapsed }) => {
    shaderUniforms.uTime.value = elapsed;
});

const shaderUniforms = {
    uTime: { value: 0 },
    uColor: { value: new THREE.Color(props.color) },
    uOpacity: { value: props.isCurrent ? 0.7 : 0.4 }
};

watch(() => props.color, (newColor) => {
    shaderUniforms.uColor.value.set(newColor);
});

watch(() => props.isCurrent, (newVal) => {
    shaderUniforms.uOpacity.value = newVal ? 0.7 : 0.4;
});

const vertexShader = `
    varying vec2 vUv;
    void main() {
        vUv = uv;
        gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
    }
`;

const fragmentShader = `
    uniform float uTime;
    uniform vec3 uColor;
    uniform float uOpacity;
    varying vec2 vUv;
    void main() {
        // Scrolling scanlines (subtler)
        float scanline = sin(vUv.y * 60.0 + uTime * 3.0) * 0.1 + 0.9;
        // Intermittent flicker (faster but shallower)
        float flicker = sin(uTime * 30.0) * 0.03 + 0.97;
        gl_FragColor = vec4(uColor, uOpacity * scanline * flicker);
    }
`;
</script>

<template>
    <TresGroup :position="position">
        <!-- Pawn Mesh -->
        <TresMesh cast-shadow>
            <TresConeGeometry :args="[0.3, 0.8, 6]" />
            <TresShaderMaterial
                v-if="effects"
                :uniforms="shaderUniforms"
                :vertex-shader="vertexShader"
                :fragment-shader="fragmentShader"
                :transparent="true"
                :depth-write="false"
                :blending="THREE.AdditiveBlending"
                :on-before-compile="(s) => { s.uniforms.uTime = shaderUniforms.uTime }"
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
        <Html v-if="mounted && gridReady" center :position="[0, 0.8, 0]" :distance-factor="6">
            <div class="pawn-overlay">
                <div class="pawn-overlay__name">{{ entity.nickname || entity.name }}</div>
                <div class="pawn-overlay__hp-bar">
                    <div class="pawn-overlay__hp-fill" :style="{ width: hpPct + '%', backgroundColor: color }"></div>
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
    box-shadow: 0 0 4px v-bind(color);
}
</style>
