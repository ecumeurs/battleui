<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import * as THREE from 'three';
import { getHexesInTile, HEX_RADIUS } from '../../Composables/useHexGrid';

const props = defineProps({
    tile: { type: Object, required: true },
    tileSize: { type: Number, default: 1.0 },
    tileHeight: { type: Number, default: 0.25 },
    effects: { type: Boolean, default: false },
});

const hexes = computed(() => getHexesInTile(props.tile.x, props.tile.y));

const position = computed(() => {
    // Parent group position is at floor level (Y=0)
    return [props.tile.x * props.tileSize, 0, props.tile.y * props.tileSize];
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
    
    hexes.value.forEach((hex, i) => {
        // Position relative to tile (gx, gy)
        dummy.position.set(hex.x - props.tile.x, h / 2, hex.z - props.tile.y);
        dummy.scale.set(1, h, 1);
        dummy.rotation.y = Math.PI / 6;
        dummy.updateMatrix();
        instancedMeshRef.value.setMatrixAt(i, dummy.matrix);

        // Robust stable randomization hash
        let hValue = (hex.hx * 374761393) ^ (hex.hy * 668265263);
        hValue = (hValue ^ (hValue >> 13)) * 1274126177;
        const hash = Math.abs(hValue ^ (hValue >> 16)) % PALETTE.length;
        
        colorObj.set(PALETTE[hash]);
        
        // Copper/Rust detection (Indices 3, 4, 5 are warmer/metallic)
        // 50% more reflexive = half the roughness
        if (hash === 4 || hash === 3) {
            roughnessArray[i] = 0.2; // Shiny copper/rust
        } else {
            roughnessArray[i] = 0.5; // Standard iron
        }
        
        // If effects are on, darken even more
        if (props.effects) {
            colorObj.lerp(new THREE.Color('#050508'), 0.4);
        }
        
        instancedMeshRef.value.setColorAt(i, colorObj);
    });
    
    // Add custom attribute for roughness
    const roughnessAttr = new THREE.InstancedBufferAttribute(roughnessArray, 1);
    instancedMeshRef.value.geometry.setAttribute('aRoughness', roughnessAttr);
    
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
            varying float vRoughness;
            ${shader.vertexShader}
        `.replace(
            '#include <begin_vertex>',
            '#include <begin_vertex>\nvRoughness = aRoughness;'
        );
        shader.fragmentShader = `
            varying float vRoughness;
            ${shader.fragmentShader}
        `.replace(
            '#include <roughnessmap_fragment>',
            '#include <roughnessmap_fragment>\nroughnessFactor = vRoughness;'
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
    </TresGroup>
</template>
