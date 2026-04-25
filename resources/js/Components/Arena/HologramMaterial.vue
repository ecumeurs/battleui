<!-- @spec-link [[mech_hologram_shader]] -->
<script setup>
import { ref, watch } from 'vue';
import { useLoop } from '@tresjs/core';
import * as THREE from 'three';

const props = defineProps({
    color: { type: String, default: '#ffffff' },
    opacity: { type: Number, default: 0.4 },
});

const { onRender } = useLoop();
const shaderUniforms = {
    uTime: { value: 0 },
    uColor: { value: new THREE.Color(props.color) },
    uOpacity: { value: props.opacity }
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
        // Scrolling scanlines (subtle)
        float scanline = sin(vUv.y * 80.0 + uTime * 2.0) * 0.04 + 0.96;
        // Intermittent flicker (subtle)
        float flicker = sin(uTime * 20.0) * 0.01 + 0.99;
        gl_FragColor = vec4(uColor, uOpacity * scanline * flicker);
    }
`;
</script>

<template>
    <TresShaderMaterial
        :uniforms="shaderUniforms"
        :vertex-shader="vertexShader"
        :fragment-shader="fragmentShader"
        :transparent="true"
        :depth-write="false"
        :blending="THREE.AdditiveBlending"
    />
</template>
