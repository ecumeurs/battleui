<!-- @spec-link [[ui_initiative_timeline]] -->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    turns: { type: Array, required: true }, // [{player_id, entity_id, delay, name}]
    teamColors: { type: Object, default: () => ({}) },
    currentEntityId: { type: String, default: '' },
});

const maxDelay = computed(() => {
    const max = Math.max(...props.turns.map(t => t.delay), 1);
    return max + 50; // Add padding
});

function tokenPosition(delay) {
    return Math.min((delay / maxDelay.value) * 100, 98);
}

function tokenColor(turn) {
    if (turn.is_self) return '#00a8ff';
    return props.teamColors[turn.team] || '#ff00ff';
}
</script>

<template>
    <div class="timeline">
        <div class="timeline__header">
            <span class="timeline__header-icon">◈</span>
            INITIATIVE SEQUENCE
        </div>

        <div class="timeline__track">
            <!-- Track background with ticks -->
            <div class="timeline__ticks">
                <div
                    v-for="i in 10"
                    :key="i"
                    class="timeline__tick"
                    :style="{ left: (i * 10) + '%' }"
                >
                    <span class="timeline__tick-label">{{ Math.round((i * 10 / 100) * maxDelay) }}</span>
                </div>
            </div>

            <!-- Active marker -->
            <div class="timeline__active-zone"></div>

            <!-- Character tokens -->
            <div
                v-for="turn in turns"
                :key="turn.entity_id"
                class="timeline__token"
                :class="{ 'timeline__token--active': turn.entity_id === currentEntityId }"
                :style="{
                    left: tokenPosition(turn.delay) + '%',
                    '--token-color': tokenColor(turn),
                }"
                :title="turn.name + ' (Delay: ' + turn.delay + ')'"
            >
                <div class="timeline__token-pip"></div>
                <span class="timeline__token-name">{{ turn.name }}</span>
                <span class="timeline__token-delay">{{ turn.delay }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.timeline {
    background: rgba(10, 10, 11, 0.7);
    border-top: 1px solid rgba(74, 74, 79, 0.3);
    padding: 6px 12px 10px;
    backdrop-filter: blur(4px);
}

.timeline__header {
    font-family: 'Orbitron', sans-serif;
    font-size: 7px;
    letter-spacing: 0.3em;
    text-transform: uppercase;
    color: rgba(0, 242, 255, 0.5);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.timeline__header-icon {
    font-size: 8px;
}

.timeline__track {
    position: relative;
    height: 40px;
    background: rgba(26, 26, 30, 0.6);
    border: 1px solid rgba(74, 74, 79, 0.2);
    overflow: visible;
}

.timeline__active-zone {
    position: absolute;
    left: 0;
    top: 0;
    width: 3%;
    height: 100%;
    background: linear-gradient(90deg, rgba(0, 242, 255, 0.1), transparent);
    border-right: 1px solid rgba(0, 242, 255, 0.2);
}

.timeline__ticks {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.timeline__tick {
    position: absolute;
    top: 0;
    height: 100%;
    width: 1px;
    background: rgba(74, 74, 79, 0.15);
}

.timeline__tick-label {
    position: absolute;
    bottom: -12px;
    left: 50%;
    transform: translateX(-50%);
    font-family: 'JetBrains Mono', monospace;
    font-size: 6px;
    color: rgba(0, 242, 255, 0.35);
}

.timeline__token {
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1px;
    cursor: pointer;
    transition: left 0.5s ease;
    z-index: 5;
}

.timeline__token-pip {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--token-color);
    border: 1.5px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 0 6px var(--token-color);
    transition: all 0.3s;
}

.timeline__token--active .timeline__token-pip {
    width: 14px;
    height: 14px;
    box-shadow: 0 0 12px var(--token-color), 0 0 24px var(--token-color);
    animation: token-glow 1.5s ease-in-out infinite;
}

.timeline__token-name {
    font-family: 'JetBrains Mono', monospace;
    font-size: 6px;
    color: #e0e0e0;
    text-transform: uppercase;
    white-space: nowrap;
    text-shadow: 0 0 3px rgba(0, 0, 0, 0.8);
}

.timeline__token-delay {
    font-family: 'JetBrains Mono', monospace;
    font-size: 7px;
    color: var(--token-color);
    opacity: 0.7;
}

@keyframes token-glow {
    0%, 100% { box-shadow: 0 0 12px var(--token-color), 0 0 24px var(--token-color); }
    50% { box-shadow: 0 0 18px var(--token-color), 0 0 36px var(--token-color); }
}
</style>
