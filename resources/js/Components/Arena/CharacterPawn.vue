<!-- @spec-link [[ui_character_pawn]] -->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    entity: { type: Object, required: true },
    teamColor: { type: String, default: '#00a8ff' },
    isActive: { type: Boolean, default: false },
    shadeOffset: { type: Number, default: 0 },
});

const hpPct = computed(() => {
    if (!props.entity.max_hp) return 0;
    return Math.round((props.entity.hp / props.entity.max_hp) * 100);
});

/* Shift hue slightly per character for distinct shades */
const pawnColor = computed(() => {
    if (!props.shadeOffset) return props.teamColor;
    // Parse hex to HSL, shift hue
    const hex = props.teamColor.replace('#', '');
    const r = parseInt(hex.substr(0, 2), 16) / 255;
    const g = parseInt(hex.substr(2, 2), 16) / 255;
    const b = parseInt(hex.substr(4, 2), 16) / 255;
    const max = Math.max(r, g, b), min = Math.min(r, g, b);
    let h = 0, s = 0, l = (max + min) / 2;
    if (max !== min) {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        if (max === r) h = ((g - b) / d + (g < b ? 6 : 0)) / 6;
        else if (max === g) h = ((b - r) / d + 2) / 6;
        else h = ((r - g) / d + 4) / 6;
    }
    h = ((h * 360) + (props.shadeOffset * 20)) % 360;
    s = Math.min(s * 100, 100);
    l = Math.min(l * 100, 85);
    return `hsl(${h}, ${s}%, ${l}%)`;
});
</script>

<template>
    <div class="pawn" :class="{ 'pawn--active': isActive }">
        <!-- Floating label -->
        <div class="pawn__label">
            <span class="pawn__name">{{ entity.name }}</span>
            <div class="pawn__hp-bar">
                <div class="pawn__hp-fill" :style="{ width: hpPct + '%', background: pawnColor }"></div>
            </div>
        </div>

        <!-- Head (sphere with direction indent) -->
        <div class="pawn__head" :style="{ borderColor: pawnColor, boxShadow: '0 0 8px ' + pawnColor + '80' }">
            <div class="pawn__direction" :style="{ background: pawnColor }"></div>
        </div>

        <!-- Body (hexagonal cone shape) -->
        <div class="pawn__body" :style="{ borderBottomColor: pawnColor + 'cc', filter: 'drop-shadow(0 0 6px ' + pawnColor + '60)' }">
            <!-- Scanline overlay for holographic effect -->
            <div class="pawn__scanlines"></div>
        </div>

        <!-- Ground shadow -->
        <div class="pawn__shadow" :style="{ background: pawnColor + '20' }"></div>
    </div>
</template>

<style scoped>
.pawn {
    position: absolute;
    display: flex;
    flex-direction: column;
    align-items: center;
    transform: translate(-50%, -80%);
    z-index: 10;
    animation: holo-float 3s ease-in-out infinite;
    cursor: pointer;
}

.pawn--active {
    z-index: 20;
    animation: holo-float 2s ease-in-out infinite, holo-pulse 1.5s ease-in-out infinite;
}

.pawn__label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    margin-bottom: 4px;
    white-space: nowrap;
}

.pawn__name {
    font-family: 'JetBrains Mono', monospace;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    color: #e0e0e0;
    text-shadow: 0 0 4px rgba(0, 0, 0, 0.8);
    background: rgba(10, 10, 11, 0.6);
    padding: 1px 4px;
}

.pawn__hp-bar {
    width: 28px;
    height: 3px;
    background: rgba(26, 26, 30, 0.8);
    border: 1px solid rgba(74, 74, 79, 0.3);
    overflow: hidden;
}

.pawn__hp-fill {
    height: 100%;
    transition: width 0.5s ease;
}

.pawn__head {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 1.5px solid;
    background: rgba(10, 10, 11, 0.4);
    position: relative;
    margin-bottom: 1px;
}

.pawn__direction {
    position: absolute;
    width: 3px;
    height: 3px;
    border-radius: 50%;
    top: 1px;
    left: 50%;
    transform: translateX(-50%);
    opacity: 0.8;
}

.pawn__body {
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 18px solid;
    position: relative;
    overflow: hidden;
    animation: pawn-rotate 8s linear infinite;
}

.pawn__scanlines {
    position: absolute;
    inset: 0;
    background: repeating-linear-gradient(
        0deg,
        transparent,
        transparent 1px,
        rgba(0, 0, 0, 0.15) 1px,
        rgba(0, 0, 0, 0.15) 2px
    );
    pointer-events: none;
    animation: scanline-shift 0.1s linear infinite;
}

.pawn__shadow {
    width: 14px;
    height: 4px;
    border-radius: 50%;
    margin-top: 2px;
    filter: blur(2px);
}

/* Holographic glitch effect */
@keyframes holo-float {
    0%, 100% { transform: translate(-50%, -80%); }
    50% { transform: translate(-50%, -83%); }
}

@keyframes holo-pulse {
    0%, 100% { opacity: 1; }
    30% { opacity: 0.85; }
    60% { opacity: 1; }
    65% { opacity: 0.4; }
    70% { opacity: 1; }
}

@keyframes pawn-rotate {
    0% { transform: perspective(100px) rotateY(0deg); }
    100% { transform: perspective(100px) rotateY(360deg); }
}

@keyframes scanline-shift {
    0% { background-position: 0 0; }
    100% { background-position: 0 2px; }
}

/* Intermittent glitch (holographic static) */
.pawn::after {
    content: '';
    position: absolute;
    inset: -2px;
    background: linear-gradient(transparent 0%, rgba(0, 242, 255, 0.03) 50%, transparent 100%);
    opacity: 0;
    animation: glitch-static 4s ease-in-out infinite;
    pointer-events: none;
}

@keyframes glitch-static {
    0%, 90%, 100% { opacity: 0; }
    92% { opacity: 1; transform: translate(1px, -1px); }
    94% { opacity: 0; }
    96% { opacity: 0.7; transform: translate(-1px, 1px); }
    98% { opacity: 0; }
}
</style>
