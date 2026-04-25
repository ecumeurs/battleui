<script setup>
import { useLoop } from '@tresjs/core';
import * as THREE from 'three';

const props = defineProps({
    color: { type: String, default: '#00ffcc' },
    type: { type: String, default: 'move' }, // 'move' or 'attack'
});

const { onRender } = useLoop();
const shaderUniforms = {
    uTime: { value: 0 },
    uColor: { value: new THREE.Color(props.color) },
    uIsAttack: { value: props.type === 'attack' ? 1.0 : 0.0 }
};

onRender(({ elapsed }) => {
    shaderUniforms.uTime.value = elapsed;
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
    uniform float uIsAttack;
    varying vec2 vUv;
    void main() {
        float dist = distance(vUv, vec2(0.5));
        if (dist > 0.5) discard;
        
        // Base border effect
        float border = smoothstep(0.45, 0.48, dist) - smoothstep(0.48, 0.5, dist);
        
        // Inner glow
        float glow = (1.0 - dist * 2.0) * 0.2;
        
        // Pulsing for attack type
        float pulse = 1.0;
        if (uIsAttack > 0.5) {
            pulse = sin(uTime * 10.0) * 0.5 + 0.5;
        }
        
        float alpha = (border + glow) * (0.3 + pulse * 0.4);
        gl_FragColor = vec4(uColor, alpha);
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
