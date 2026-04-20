<script setup>
import { computed } from 'vue';

const props = defineProps({
    teamHp: { type: Number, required: true },
    teamMaxHp: { type: Number, required: true },
    charsRemaining: { type: Number, required: true },
    totalChars: { type: Number, required: true },
    isEnemy: { type: Boolean, default: false },
});

const hpPct = computed(() => Math.round((props.teamHp / props.teamMaxHp) * 100));

function barColor(pct) {
    if (pct > 60) return '#39ff13';
    if (pct > 30) return '#ff8c00';
    return '#ff2020';
}

function barGlow(pct) {
    const c = barColor(pct);
    return `0 0 10px ${c}, 0 0 20px ${c}40`;
}
</script>

<template>
    <div class="team-summary" :class="{ 'team-summary--right': isEnemy, 'team-summary--left': !isEnemy }">
        <!-- Chars icons (left for allies, right for enemies) -->
        <div v-if="!isEnemy" class="team-summary__chars">
            <span class="team-summary__char-icon" v-for="n in totalChars" :key="n" :class="{ 'team-summary__char-icon--alive': n <= charsRemaining }">◆</span>
        </div>

        <div class="team-summary__hp-bar-container">
            <span v-if="isEnemy" class="team-summary__hp-text team-summary__hp-text--right" :style="{ color: barColor(hpPct) }">{{ teamHp }}</span>
            
            <div class="team-summary__hp-bar" :class="{ 'team-summary__hp-bar--left': !isEnemy, 'team-summary__hp-bar--right': isEnemy }">
                <div
                    class="team-summary__hp-fill"
                    :class="{ 'team-summary__hp-fill--left': !isEnemy, 'team-summary__hp-fill--right': isEnemy }"
                    :style="{
                        width: hpPct + '%',
                        background: isEnemy 
                            ? `linear-gradient(270deg, ${barColor(hpPct)}80, ${barColor(hpPct)})`
                            : `linear-gradient(90deg, ${barColor(hpPct)}80, ${barColor(hpPct)})`,
                        boxShadow: barGlow(hpPct)
                    }"
                ></div>
            </div>

            <span v-if="!isEnemy" class="team-summary__hp-text" :style="{ color: barColor(hpPct) }">{{ teamHp }}</span>
        </div>

        <div v-if="isEnemy" class="team-summary__chars">
            <span class="team-summary__char-icon" v-for="n in totalChars" :key="n" :class="{ 'team-summary__char-icon--alive team-summary__char-icon--enemy': n <= charsRemaining }">◆</span>
        </div>
    </div>
</template>

<style scoped>
.team-summary {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
}

.team-summary--left {
    justify-content: flex-start;
}

.team-summary--right {
    justify-content: flex-end;
}

.team-summary__chars {
    display: flex;
    gap: 3px;
    flex-shrink: 0;
}

.team-summary__char-icon {
    font-size: 10px;
    color: #2a2a2f;
    transition: color 0.3s;
}

.team-summary__char-icon--alive {
    color: #39ff13;
    text-shadow: 0 0 6px #39ff1380;
}

.team-summary__char-icon--enemy {
    color: #ff2020;
    text-shadow: 0 0 6px #ff202080;
}

.team-summary__hp-bar-container {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 8px;
}

.team-summary__hp-bar {
    flex: 1;
    height: 18px;
    background: rgba(26, 26, 30, 0.9);
    border: 1px solid rgba(74, 74, 79, 0.3);
    overflow: hidden;
    position: relative;
}

/* Subtle texture inside bar */
.team-summary__hp-bar::after {
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

.team-summary__hp-fill {
    height: 100%;
    transition: width 0.8s ease, background 0.5s ease;
}

.team-summary__hp-fill--left {
    float: right;
}

.team-summary__hp-fill--right {
    float: left;
}

.team-summary__hp-text {
    font-family: 'Orbitron', sans-serif;
    font-size: 14px;
    font-weight: 700;
    min-width: 36px;
    text-align: center;
}

.team-summary__hp-text--right {
    text-align: center;
}
</style>
