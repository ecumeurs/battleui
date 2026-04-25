<!-- @spec-link [[mech_hologram_shader]] -->
<script setup>
import { ref, watch } from 'vue';
import { useLoop } from '@tresjs/core';
import * as THREE from 'three';

const props = defineProps({
    color: { type: String, default: '#ffffff' },
    opacity: { type: Number, default: 0.4 },
    glowIntensity: { type: Number, default: 1.0 },
});

const { onRender } = useLoop();
const shaderUniforms = {
    uTime: { value: 0 },
    uColor: { value: new THREE.Color(props.color) },
    uOpacity: { value: props.opacity },
    uGlowIntensity: { value: props.glowIntensity }
};

onRender(({ elapsed }) => {
    shaderUniforms.uTime.value = elapsed;
});

watch(() => props.color, (newColor) => {
    shaderUniforms.uColor.value.set(newColor);
});

watch(() => props.opacity, (newVal) => {
    shaderUniforms.uOpacity.value = newVal;
});


watch(() => props.glowIntensity, (newVal) => {
    shaderUniforms.uGlowIntensity.value = newVal;
});


const fragmentShader = `
    uniform float uTime;
    uniform vec3 uColor;
    uniform float uOpacity;
    uniform float uGlowIntensity;
    varying vec2 vUv;
    varying vec3 vNormal;
    varying vec3 vViewPosition;
    varying vec3 vWorldOrigin;

    float hash(float n) { return fract(sin(n) * 43758.5453123); }

    void main() {
        // Fresnel-like edge glow
        vec3 normal = normalize(vNormal);
        vec3 viewDir = normalize(vViewPosition);
        float edge = 1.0 - max(dot(normal, viewDir), 0.0);
        edge = pow(edge, 3.0);

        // Periodic Glitch/Flicker (Once every 20s)
        // Using world origin as seed so each object flickers at different times
        float seed = vWorldOrigin.x * 12.9898 + vWorldOrigin.z * 78.233;
        float cycleTime = mod(uTime + hash(seed) * 20.0, 20.0);
        
        // Flicker window: 0.2s to 1.0s
        float flickerDuration = 0.2 + hash(seed + 1.0) * 0.8;
        float flickerIntensity = 1.0;
        
        if (cycleTime < flickerDuration) {
            // Rapid chaotic flickering during the glitch window
            float glitch = sin(uTime * 50.0 + hash(seed) * 100.0) * 0.5 + 0.5;
            // Occasional full drop-out
            if (hash(uTime + seed) > 0.8) glitch = 0.0;
            flickerIntensity = glitch;
        }

        // Scrolling scanlines
        float scanline = sin(vUv.y * 100.0 + uTime * 3.0) * 0.1 + 0.9;
        
        // Intermittent flicker (The base "hum")
        float hum = sin(uTime * 15.0) * 0.02 + 0.98;

        // High-Energy "Radiation" Glow (Toned down further)
        float radiationPulse = sin(uTime * 3.0 + seed) * 0.1 + 1.0;
        vec3 glowColor = uColor * 1.5 + vec3(0.2, 0.2, 0.2); // Subtle core
        vec3 glow = edge * glowColor * uGlowIntensity * radiationPulse;
        
        vec3 finalColor = uColor + glow;
        
        float finalOpacity = uOpacity * scanline * hum * flickerIntensity;
        
        gl_FragColor = vec4(finalColor, finalOpacity);
    }
`;

const vertexShader = `
    varying vec2 vUv;
    varying vec3 vNormal;
    varying vec3 vViewPosition;
    varying vec3 vWorldOrigin;
    uniform float uTime;

    void main() {
        vUv = uv;
        vNormal = normalize(normalMatrix * normal);
        
        // Get world origin for the whole object to ensure rigid motion
        vec4 worldOrigin = modelMatrix * vec4(0.0, 0.0, 0.0, 1.0);
        vWorldOrigin = worldOrigin.xyz;
        float offset = sin(worldOrigin.x * 0.5 + worldOrigin.z * 0.5) * 6.28;
        
        // Minute bobbing motion (Floating) - applied uniformly to all vertices
        // Using (sin * 0.5 + 0.5) ensures the value is always >= 0, so it never dips below ground
        float bob = (sin(uTime * 1.0 + offset) * 0.5 + 0.5) * 0.08;
        
        vec4 mvPosition = modelViewMatrix * vec4(position.x, position.y + bob, position.z, 1.0);
        vViewPosition = -mvPosition.xyz;
        gl_Position = projectionMatrix * mvPosition;
    }
`;
</script>

<template>
    <TresShaderMaterial
        :uniforms="shaderUniforms"
        :vertex-shader="vertexShader"
        :fragment-shader="fragmentShader"
        :transparent="true"
        :depth-write="true"
        :blending="THREE.NormalBlending"
    />
</template>
