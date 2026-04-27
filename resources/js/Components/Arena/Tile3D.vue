<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import * as THREE from 'three';
import { getHexesInTile, HEX_RADIUS } from '../../Composables/useHexGrid';
import HologramMaterial from './HologramMaterial.vue';

const props = defineProps({
    tile: { type: Object, required: true },
    tileSize: { type: Number, default: 1.0 },
    tileHeight: { type: Number, default: 0.25 },
    effects: { type: Boolean, default: false },
});

const hexes = computed(() => getHexesInTile(props.tile.x + 0.5, props.tile.y + 0.5));

const position = computed(() => {
    // Parent group position is at floor level (Y=0)
    return [(props.tile.x + 0.5) * props.tileSize, 0, (props.tile.y + 0.5) * props.tileSize];
});


const instancedMeshRef = ref();

const PALETTE = [
    '#2c2c2e', // Deep Iron
    '#1c1c1e', // Dark Steel
    '#3a3a3e', // Burnished Metal
    '#5d2e0a', // Aged Rust
    '#7d441f', // Deep Copper
    '#4a3728', // Earthy Brown
    '#252525', // Charcoal
];

function updateMatrices() {
    if (!instancedMeshRef.value) return;
    
    const dummy = new THREE.Object3D();
    const colorObj = new THREE.Color();
    const h = (props.tile.height + 1) * props.tileHeight;
    
    const roughnessArray = new Float32Array(hexes.value.length);
    const metalnessArray = new Float32Array(hexes.value.length);
    
    hexes.value.forEach((hex, i) => {
        // Position relative to tile (gx, gy)
        // Add +0.125 X correction to center the hex cluster within the logical tile
        dummy.position.set(hex.x - (props.tile.x + 0.5) + 0.125, h / 2, hex.z - (props.tile.y + 0.5));
        dummy.scale.set(1, h, 1);
        dummy.rotation.y = Math.PI / 6;
        dummy.updateMatrix();
        instancedMeshRef.value.setMatrixAt(i, dummy.matrix);

        // Robust stable randomization hash
        let hValue = (hex.hx * 374761393) ^ (hex.hy * 668265263);
        hValue = (hValue ^ (hValue >> 13)) * 1274126177;
        const hash = Math.abs(hValue ^ (hValue >> 16)) % PALETTE.length;
        
        colorObj.set(PALETTE[hash]);
        
        // Copper/Rust detection (Indices 3, 4 are warmer/metallic)
        if (hash === 4 || hash === 3) {
            roughnessArray[i] = 0.05; // Extra shiny
            metalnessArray[i] = 1.0;  // Fully metallic
        } else {
            roughnessArray[i] = 0.5;  // Standard iron
            metalnessArray[i] = 0.7;
        }
        
        // If effects are on, darken even more
        if (props.effects) {
            colorObj.lerp(new THREE.Color('#050508'), 0.4);
        }
        
        instancedMeshRef.value.setColorAt(i, colorObj);
    });
    
    // Add custom attributes for roughness and metalness
    const roughnessAttr = new THREE.InstancedBufferAttribute(roughnessArray, 1);
    const metalnessAttr = new THREE.InstancedBufferAttribute(metalnessArray, 1);
    instancedMeshRef.value.geometry.setAttribute('aRoughness', roughnessAttr);
    instancedMeshRef.value.geometry.setAttribute('aMetalness', metalnessAttr);
    
    instancedMeshRef.value.instanceMatrix.needsUpdate = true;
    if (instancedMeshRef.value.instanceColor) {
        instancedMeshRef.value.instanceColor.needsUpdate = true;
    }
}

onMounted(updateMatrices);
watch(() => props.tile.height, updateMatrices);

function onMaterialReady(mat) {
    mat.onBeforeCompile = (shader) => {
        shader.vertexShader = `
            attribute float aRoughness;
            attribute float aMetalness;
            varying float vRoughness;
            varying float vMetalness;
            ${shader.vertexShader}
        `.replace(
            '#include <begin_vertex>',
            '#include <begin_vertex>\nvRoughness = aRoughness;\nvMetalness = aMetalness;'
        );
        shader.fragmentShader = `
            varying float vRoughness;
            varying float vMetalness;
            ${shader.fragmentShader}
        `.replace(
            '#include <roughnessmap_fragment>',
            '#include <roughnessmap_fragment>\nroughnessFactor = vRoughness;'
        ).replace(
            '#include <metalnessmap_fragment>',
            '#include <metalnessmap_fragment>\nmetalnessFactor = vMetalness;'
        );
    };
}
</script>

<template>
    <TresGroup :position="position">
        <TresInstancedMesh
            ref="instancedMeshRef"
            :args="[null, null, hexes.length]"
            receive-shadow
            :user-data="{ gx: tile.x, gy: tile.y }"
        >
            <TresCylinderGeometry :args="[HEX_RADIUS * 0.97, HEX_RADIUS * 0.97, 1, 6]" />
            <TresMeshStandardMaterial
                :color="'#ffffff'"
                :emissive="'#000000'"
                :roughness="0.5"
                :metalness="0.7"
                @ready="onMaterialReady"
            />
        </TresInstancedMesh>

        <!-- Hollow logical grid indicator (Hologram Frame) -->
        <TresGroup :position="[0, (tile.height + 1) * tileHeight + 0.02, 0]">
            <!-- North edge -->
            <TresMesh :position="[0, 0, -tileSize * 0.5]">
                <TresBoxGeometry :args="[tileSize, 0.01, 0.01]" />
                <HologramMaterial color="#00f2ff" :opacity="0.15" :glow-intensity="0.4" :floating="false" />
            </TresMesh>
            <!-- South edge -->
            <TresMesh :position="[0, 0, tileSize * 0.5]">
                <TresBoxGeometry :args="[tileSize, 0.01, 0.01]" />
                <HologramMaterial color="#00f2ff" :opacity="0.15" :glow-intensity="0.4" :floating="false" />
            </TresMesh>
            <!-- East edge -->
            <TresMesh :position="[tileSize * 0.5, 0, 0]">
                <TresBoxGeometry :args="[0.01, 0.01, tileSize]" />
                <HologramMaterial color="#00f2ff" :opacity="0.15" :glow-intensity="0.4" :floating="false" />
            </TresMesh>
            <!-- West edge -->
            <TresMesh :position="[-tileSize * 0.5, 0, 0]">
                <TresBoxGeometry :args="[0.01, 0.01, tileSize]" />
                <HologramMaterial color="#00f2ff" :opacity="0.15" :glow-intensity="0.4" :floating="false" />
            </TresMesh>
        </TresGroup>
    </TresGroup>
</template>
