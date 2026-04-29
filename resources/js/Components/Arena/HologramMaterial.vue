<!-- @spec-link [[mech_hologram_shader]] -->
<script setup>
import { ref, watch } from 'vue';
import { useLoop } from '@tresjs/core';
import * as THREE from 'three';

const props = defineProps({
    /** Base color of the hologram */
    color: { type: String, default: '#ffffff' },
    /** Master opacity of the material */
    opacity: { type: Number, default: 0.4 },
    /** Fresnel edge glow strength */
    glowIntensity: { type: Number, default: 1.0 },

    // --- Hovering / Floating ---
    /** Enable/Disable the vertical bobbing animation */
    floating: { type: Boolean, default: true },
    /** Speed of the bobbing animation */
    floatSpeed: { type: Number, default: 1.0 },
    /** Maximum vertical height of the bobbing motion */
    floatHeight: { type: Number, default: 0.08 },

    // --- Flickering ---
    /** Enable/Disable the random glitch/flicker effect */
    flicker: { type: Boolean, default: true },
    /** Time between major flicker events (seconds) */
    flickerPeriod: { type: Number, default: 40.0 },
    /** Minimum opacity during a flicker glitch */
    flickerMin: { type: Number, default: 0.01 },
    /** Maximum opacity during a flicker glitch */
    flickerMax: { type: Number, default: 0.25 },

    // --- Radiation / Bloom ---
    /** Enable/Disable high-energy radiation pulses (pulsing edge glow) */
    radiation: { type: Boolean, default: true },
    /** Intensity multiplier for the radiation pulse effect */
    radiationIntensity: { type: Number, default: 1.0 },

    // --- Scanlines ---
    /** Enable/Disable horizontal scrolling scanlines */
    scanlines: { type: Boolean, default: true },
    /** Density/Number of scanlines across the Y axis */
    scanlineDensity: { type: Number, default: 100.0 },
    /** Speed of the scanline scroll animation */
    scanlineSpeed: { type: Number, default: 3.0 },
});

const { onRender } = useLoop();
const shaderUniforms = {
    uTime: { value: 0 },
    uColor: { value: new THREE.Color(props.color) },
    uOpacity: { value: props.opacity },
    uGlowIntensity: { value: props.glowIntensity },
    // Floating
    uFloating: { value: props.floating ? 1.0 : 0.0 },
    uFloatSpeed: { value: props.floatSpeed },
    uFloatHeight: { value: props.floatHeight },
    // Flickering
    uFlicker: { value: props.flicker ? 1.0 : 0.0 },
    uFlickerPeriod: { value: props.flickerPeriod },
    uFlickerMin: { value: props.flickerMin },
    uFlickerMax: { value: props.flickerMax },
    // Radiation
    uRadiation: { value: props.radiation ? 1.0 : 0.0 },
    uRadiationIntensity: { value: props.radiationIntensity },
    // Scanlines
    uScanlines: { value: props.scanlines ? 1.0 : 0.0 },
    uScanlineDensity: { value: props.scanlineDensity },
    uScanlineSpeed: { value: props.scanlineSpeed },
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

watch(() => props.floating, (newVal) => {
    shaderUniforms.uFloating.value = newVal ? 1.0 : 0.0;
});

watch(() => props.floatSpeed, (newVal) => { shaderUniforms.uFloatSpeed.value = newVal; });
watch(() => props.floatHeight, (newVal) => { shaderUniforms.uFloatHeight.value = newVal; });

watch(() => props.flicker, (newVal) => { shaderUniforms.uFlicker.value = newVal ? 1.0 : 0.0; });
watch(() => props.flickerPeriod, (newVal) => { shaderUniforms.uFlickerPeriod.value = newVal; });
watch(() => props.flickerMin, (newVal) => { shaderUniforms.uFlickerMin.value = newVal; });
watch(() => props.flickerMax, (newVal) => { shaderUniforms.uFlickerMax.value = newVal; });

watch(() => props.radiation, (newVal) => { shaderUniforms.uRadiation.value = newVal ? 1.0 : 0.0; });
watch(() => props.radiationIntensity, (newVal) => { shaderUniforms.uRadiationIntensity.value = newVal; });

watch(() => props.scanlines, (newVal) => { shaderUniforms.uScanlines.value = newVal ? 1.0 : 0.0; });
watch(() => props.scanlineDensity, (newVal) => { shaderUniforms.uScanlineDensity.value = newVal; });
watch(() => props.scanlineSpeed, (newVal) => { shaderUniforms.uScanlineSpeed.value = newVal; });


const fragmentShader = `
    uniform float uTime;
    uniform vec3 uColor;
    uniform float uOpacity;
    uniform float uGlowIntensity;

    uniform float uFlicker;
    uniform float uFlickerPeriod;
    uniform float uFlickerMin;
    uniform float uFlickerMax;

    uniform float uRadiation;
    uniform float uRadiationIntensity;

    uniform float uScanlines;
    uniform float uScanlineDensity;
    uniform float uScanlineSpeed;

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

        // Periodic Glitch/Flicker
        float seed = vWorldOrigin.x * 12.9898 + vWorldOrigin.z * 78.233;
        float cycleTime = mod(uTime + hash(seed) * uFlickerPeriod, uFlickerPeriod);
        
        // Flicker window: 0.2s to 1.0s
        float flickerDuration = 0.2 + hash(seed + 1.0) * 0.8;
        float flickerIntensity = 1.0;
        
        if (uFlicker > 0.5 && cycleTime < flickerDuration) {
            float glitch = (sin(uTime * 50.0 + hash(seed) * 100.0) * 0.5 + 0.5);
            // Remap glitch to [uFlickerMin, uFlickerMax]
            glitch = uFlickerMin + glitch * (uFlickerMax - uFlickerMin);
            
            // Occasional full drop-out
            if (hash(uTime + seed) > 0.98) glitch = 0.0;
            flickerIntensity = glitch;
        }

        // Scrolling scanlines
        float scanline = 1.0;
        if (uScanlines > 0.5) {
            scanline = sin(vUv.y * uScanlineDensity + uTime * uScanlineSpeed) * 0.1 + 0.9;
        }
        
        // Intermittent flicker (The base "hum")
        float hum = sin(uTime * 15.0) * 0.02 + 0.98;

        // High-Energy "Radiation" Glow
        float radiationPulse = 1.0;
        if (uRadiation > 0.5) {
            radiationPulse = (sin(uTime * 3.0 + seed) * 0.1 + 1.0) * uRadiationIntensity;
        }

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
    uniform float uFloating;
    uniform float uFloatSpeed;
    uniform float uFloatHeight;

    void main() {
        vUv = uv;
        vNormal = normalize(normalMatrix * normal);
        
        // Get world origin for the whole object to ensure rigid motion
        vec4 worldOrigin = modelMatrix * vec4(0.0, 0.0, 0.0, 1.0);
        vWorldOrigin = worldOrigin.xyz;
        float offset = sin(worldOrigin.x * 0.5 + worldOrigin.z * 0.5) * 6.28;
        
        // Minute bobbing motion (Floating) - applied uniformly to all vertices
        // Using (sin * 0.5 + 0.5) ensures the value is always >= 0, so it never dips below ground
        float bob = (sin(uTime * uFloatSpeed + offset) * 0.5 + 0.5) * uFloatHeight * uFloating;
        
        vec4 mvPosition = modelViewMatrix * vec4(position.x, position.y + bob, position.z, 1.0);
        vViewPosition = -mvPosition.xyz;
        gl_Position = projectionMatrix * mvPosition;
    }
`;
</script>

<template>
    <TresShaderMaterial :uniforms="shaderUniforms" :vertex-shader="vertexShader" :fragment-shader="fragmentShader"
        :transparent="true" :depth-write="true" :blending="THREE.NormalBlending" />
</template>
