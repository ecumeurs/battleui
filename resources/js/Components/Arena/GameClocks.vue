<script setup>
import { computed } from 'vue';

const props = defineProps({
    matchDuration: { type: String, default: '00:00' },
    shotClock: { type: Number, default: 30 },
    isSocketConnected: { type: Boolean, default: false },
});

const shotClockClass = computed(() => {
    if (props.shotClock <= 5) return 'shot-clock--critical';
    if (props.shotClock <= 10) return 'shot-clock--warning';
    return '';
});
</script>

<template>
    <div class="game-clocks">
        <div class="game-clocks__match-time">
            <span class="game-clocks__socket-indicator" :class="{ 'game-clocks__socket-indicator--online': isSocketConnected }"></span>
            {{ matchDuration }}
        </div>
        <div class="game-clocks__divider"></div>
        <div class="game-clocks__shot-clock" :class="shotClockClass">
            ⏱ {{ String(shotClock).padStart(2, '0') }}
        </div>
    </div>
</template>

<style scoped>
.game-clocks {
    width: 120px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    position: relative;
    z-index: 1;
}

.game-clocks__match-time {
    font-family: 'Orbitron', sans-serif;
    font-size: var(--fs-lg);
    color: #e0e0e0;
    letter-spacing: 0.1em;
    display: flex;
    align-items: center;
    gap: 8px;
}

.game-clocks__socket-indicator {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--color-red);
    box-shadow: 0 0 4px var(--color-red);
    transition: background 0.3s, box-shadow 0.3s;
}

.game-clocks__socket-indicator--online {
    background: var(--color-lime);
    box-shadow: 0 0 8px var(--color-lime);
    animation: beacon 2s infinite;
}

@keyframes beacon {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

.game-clocks__divider {
    width: 40px;
    height: 1px;
    background: linear-gradient(90deg, transparent, #4a4a4f, transparent);
}

.game-clocks__shot-clock {
    font-family: 'JetBrains Mono', monospace;
    font-size: var(--fs-sm);
    color: var(--color-cyan);
    text-shadow: 0 0 8px #00f2ff60;
    transition: color 0.3s, text-shadow 0.3s;
}

.shot-clock--warning {
    color: var(--color-orange);
    text-shadow: 0 0 10px #ff8c0060;
    animation: pulse-warn 1s ease-in-out infinite;
}

.shot-clock--critical {
    color: var(--color-red);
    text-shadow: 0 0 12px #ff202080;
    animation: pulse-crit 0.5s ease-in-out infinite;
}

@keyframes pulse-warn {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

@keyframes pulse-crit {
    0%, 100% { opacity: 1; text-shadow: 0 0 16px #ff2020; }
    50% { opacity: 0.6; text-shadow: 0 0 4px #ff2020; }
}
</style>
