<!-- @spec-link [[ui_combat_header]] -->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    allyTeamHp: { type: Number, default: 45 },
    allyTeamMaxHp: { type: Number, default: 45 },
    allyCharsRemaining: { type: Number, default: 3 },
    allyTotalChars: { type: Number, default: 3 },
    enemyTeamHp: { type: Number, default: 38 },
    enemyTeamMaxHp: { type: Number, default: 42 },
    enemyCharsRemaining: { type: Number, default: 3 },
    enemyTotalChars: { type: Number, default: 3 },
    matchDuration: { type: String, default: '04:32' },
    shotClock: { type: Number, default: 23 },
    isSocketConnected: { type: Boolean, default: false },
});

const allyHpPct = computed(() => Math.round((props.allyTeamHp / props.allyTeamMaxHp) * 100));
const enemyHpPct = computed(() => Math.round((props.enemyTeamHp / props.enemyTeamMaxHp) * 100));

function barColor(pct) {
    if (pct > 60) return '#39ff13';
    if (pct > 30) return '#ff8c00';
    return '#ff2020';
}

function barGlow(pct) {
    const c = barColor(pct);
    return `0 0 10px ${c}, 0 0 20px ${c}40`;
}

const shotClockClass = computed(() => {
    if (props.shotClock <= 5) return 'shot-clock--critical';
    if (props.shotClock <= 10) return 'shot-clock--warning';
    return '';
});
</script>

<template>
    <div class="combat-header">
        <!-- Left side: ally team -->
        <div class="combat-header__side combat-header__side--left">
            <div class="combat-header__chars">
                <span class="combat-header__char-icon" v-for="n in allyTotalChars" :key="n" :class="{ 'combat-header__char-icon--alive': n <= allyCharsRemaining }">◆</span>
            </div>
            <div class="combat-header__hp-bar-container">
                <div class="combat-header__hp-bar combat-header__hp-bar--left">
                    <div
                        class="combat-header__hp-fill combat-header__hp-fill--left"
                        :style="{
                            width: allyHpPct + '%',
                            background: `linear-gradient(90deg, ${barColor(allyHpPct)}80, ${barColor(allyHpPct)})`,
                            boxShadow: barGlow(allyHpPct)
                        }"
                    ></div>
                </div>
                <span class="combat-header__hp-text" :style="{ color: barColor(allyHpPct) }">{{ allyTeamHp }}</span>
            </div>
        </div>

        <!-- Center: timers -->
        <div class="combat-header__center">
            <div class="combat-header__match-time">
                <span class="combat-header__socket-indicator" :class="{ 'combat-header__socket-indicator--online': isSocketConnected }"></span>
                {{ matchDuration }}
            </div>
            <div class="combat-header__divider"></div>
            <div class="combat-header__shot-clock" :class="shotClockClass">
                ⏱ {{ String(shotClock).padStart(2, '0') }}
            </div>
        </div>

        <!-- Right side: enemy team -->
        <div class="combat-header__side combat-header__side--right">
            <div class="combat-header__hp-bar-container">
                <span class="combat-header__hp-text combat-header__hp-text--right" :style="{ color: barColor(enemyHpPct) }">{{ enemyTeamHp }}</span>
                <div class="combat-header__hp-bar combat-header__hp-bar--right">
                    <div
                        class="combat-header__hp-fill combat-header__hp-fill--right"
                        :style="{
                            width: enemyHpPct + '%',
                            background: `linear-gradient(270deg, ${barColor(enemyHpPct)}80, ${barColor(enemyHpPct)})`,
                            boxShadow: barGlow(enemyHpPct)
                        }"
                    ></div>
                </div>
            </div>
            <div class="combat-header__chars">
                <span class="combat-header__char-icon" v-for="n in enemyTotalChars" :key="n" :class="{ 'combat-header__char-icon--alive combat-header__char-icon--enemy': n <= enemyCharsRemaining }">◆</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.combat-header {
    display: flex;
    align-items: stretch;
    height: 64px;
    background: rgba(10, 10, 11, 0.8);
    border-bottom: 1px solid rgba(74, 74, 79, 0.3);
    backdrop-filter: blur(6px);
    position: relative;
}

/* Scanline overlay */
.combat-header::after {
    content: '';
    position: absolute;
    inset: 0;
    background: repeating-linear-gradient(
        0deg,
        transparent,
        transparent 2px,
        rgba(0, 0, 0, 0.08) 2px,
        rgba(0, 0, 0, 0.08) 4px
    );
    pointer-events: none;
}

.combat-header__side {
    flex: 1;
    display: flex;
    align-items: center;
    padding: 0 12px;
    gap: 10px;
}

.combat-header__side--left {
    justify-content: flex-start;
}

.combat-header__side--right {
    justify-content: flex-end;
}

.combat-header__chars {
    display: flex;
    gap: 3px;
    flex-shrink: 0;
}

.combat-header__char-icon {
    font-size: 10px;
    color: #2a2a2f;
    transition: color 0.3s;
}

.combat-header__char-icon--alive {
    color: #39ff13;
    text-shadow: 0 0 6px #39ff1380;
}

.combat-header__char-icon--enemy {
    color: #ff2020;
    text-shadow: 0 0 6px #ff202080;
}

.combat-header__hp-bar-container {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 8px;
}

.combat-header__hp-bar {
    flex: 1;
    height: 18px;
    background: rgba(26, 26, 30, 0.9);
    border: 1px solid rgba(74, 74, 79, 0.3);
    overflow: hidden;
    position: relative;
}

/* Subtle texture inside bar */
.combat-header__hp-bar::after {
    content: '';
    position: absolute;
    inset: 0;
    background: repeating-linear-gradient(
        90deg,
        transparent,
        transparent 3px,
        rgba(0, 0, 0, 0.15) 3px,
        rgba(0, 0, 0, 0.15) 4px
    );
    pointer-events: none;
}

.combat-header__hp-fill {
    height: 100%;
    transition: width 0.8s ease, background 0.5s ease;
}

.combat-header__hp-fill--left {
    float: right;
}

.combat-header__hp-fill--right {
    float: left;
}

.combat-header__hp-text {
    font-family: 'Orbitron', sans-serif;
    font-size: 14px;
    font-weight: 700;
    min-width: 36px;
    text-align: center;
}

.combat-header__hp-text--right {
    text-align: center;
}

.combat-header__center {
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

.combat-header__match-time {
    font-family: 'Orbitron', sans-serif;
    font-size: 16px;
    color: #e0e0e0;
    letter-spacing: 0.1em;
    display: flex;
    align-items: center;
    gap: 8px;
}

.combat-header__socket-indicator {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #ff2020;
    box-shadow: 0 0 4px #ff2020;
    transition: background 0.3s, box-shadow 0.3s;
}

.combat-header__socket-indicator--online {
    background: #39ff13;
    box-shadow: 0 0 8px #39ff13;
    animation: beacon 2s infinite;
}

@keyframes beacon {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

.combat-header__divider {
    width: 40px;
    height: 1px;
    background: linear-gradient(90deg, transparent, #4a4a4f, transparent);
}

.combat-header__shot-clock {
    font-family: 'JetBrains Mono', monospace;
    font-size: 13px;
    color: #00f2ff;
    text-shadow: 0 0 8px #00f2ff60;
    transition: color 0.3s, text-shadow 0.3s;
}

.shot-clock--warning {
    color: #ff8c00;
    text-shadow: 0 0 10px #ff8c0060;
    animation: pulse-warn 1s ease-in-out infinite;
}

.shot-clock--critical {
    color: #ff2020;
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
