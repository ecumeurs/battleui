<script setup>
import { computed } from 'vue';

const props = defineProps({
    seed: { type: Number, default: 0 }
});

/* Perfect fit for 64x32 diamond tiles */
const height = computed(() => 24 + (props.seed % 8));

const colors = [
    '#322822', // Deep Rust
    '#3d2b1f', // Oxidized Iron
    '#3a3a3f', // Dull Steel
];

const mainColor = computed(() => colors[props.seed % colors.length]);
</script>

<template>
    <div class="holo-obstacle" :style="{ '--obs-h': height + 'px', '--obs-color': mainColor }">
        <div class="monolith">
            <!-- Left Side Facet (Perfect Rhombus) -->
            <div class="face face--left">
                <div class="scanlines"></div>
            </div>

            <!-- Right Side Facet (Perfect Rhombus) -->
            <div class="face face--right">
                <div class="scanlines"></div>
            </div>

            <!-- Top Cap (Perfect Isometric Diamond) -->
            <div class="face face--top">
                <div class="scanlines"></div>
            </div>
        </div>
        
        <!-- Base anchor glow matching tile shape perfectly (64x32) -->
        <div class="glow-base"></div>
    </div>
</template>

<style scoped>
.holo-obstacle {
    position: absolute;
    display: flex;
    flex-direction: column;
    align-items: center;
    /* Centered on the tile's geometric center (32, 16) */
    transform: translate(-50%, -50%);
    pointer-events: none;
    z-index: 5;
}

.monolith {
    position: relative;
    /* Precise 3D coordinate volume */
    width: 0; 
    height: 0;
    transform-style: preserve-3d;
}

.face {
    position: absolute;
    transform-style: preserve-3d;
    background: linear-gradient(to bottom, rgba(255, 255, 255, 0.05), var(--obs-color));
    border: 1.5px solid rgba(255, 255, 255, 0.1);
    box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.5);
}

/* TOP FACE: 45.25px square at 60deg/45deg matches 64x32 diamond */
.face--top {
    width: 45.25px;
    height: 45.25px;
    /* Center at Y = -H */
    top: calc(-1 * var(--obs-h) - 22.62px); 
    left: -22.62px;
    background: var(--obs-color);
    transform: rotateX(60deg) rotateZ(45deg);
    opacity: 0.95;
    box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.6);
    border-color: rgba(255, 255, 255, 0.15);
}

/* LEFT SIDE: Rhombus connecting (-32, 0) to (0, 16) */
.face--left {
    width: 32px;
    height: var(--obs-h);
    left: -32px;
    bottom: -16px; 
    transform-origin: 100% 100%;
    transform: skewY(26.565deg);
}

/* RIGHT SIDE: Rhombus connecting (32, 0) to (0, 16) */
.face--right {
    width: 32px;
    height: var(--obs-h);
    left: 0;
    bottom: -16px; 
    transform-origin: 0% 100%;
    transform: skewY(-26.565deg);
}

.scanlines {
    position: absolute;
    inset: 0;
    background: repeating-linear-gradient(
        0deg,
        transparent,
        transparent 1px,
        rgba(0, 0, 0, 0.4) 1px,
        rgba(0, 0, 0, 0.5) 2px
    );
    animation: scanline-shift 0.2s linear infinite;
}

.glow-base {
    width: 64px;
    height: 32px;
    background: radial-gradient(circle, rgba(61, 43, 31, 1) 0%, transparent 80%);
    border-radius: 50%;
    margin-top: -16px;
    filter: blur(8px);
    transform: scaleY(0.5); 
    opacity: 0.7;
}

@keyframes scanline-shift {
    0% { background-position: 0 0; }
    100% { background-position: 0 2px; }
}

/* Industrial glitch overlay */
.monolith::after {
    content: '';
    position: absolute;
    inset: -32px;
    background: linear-gradient(transparent 0%, rgba(255, 255, 255, 0.1) 50%, transparent 100%);
    opacity: 0;
    animation: obs-glitch 5.5s ease-in-out infinite;
    pointer-events: none;
}

@keyframes obs-glitch {
    0%, 95%, 100% { opacity: 0; transform: scaleY(1); }
    96% { opacity: 0.5; transform: skewX(12deg) scaleY(1.3); }
    97% { opacity: 0; }
    98% { opacity: 0.3; transform: translateX(-4px); }
    99% { opacity: 0; }
}
</style>
