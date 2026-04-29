<!-- @spec-link [[ui_combat_header]] -->
<script setup>
import TeamSummary from './TeamSummary.vue';
import GameClocks from './GameClocks.vue';

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
</script>

<template>
    <div class="combat-header">
        <TeamSummary 
            :team-hp="allyTeamHp"
            :team-max-hp="allyTeamMaxHp"
            :chars-remaining="allyCharsRemaining"
            :total-chars="allyTotalChars"
            :is-enemy="false"
        />

        <GameClocks 
            :match-duration="matchDuration"
            :shot-clock="shotClock"
            :is-socket-connected="isSocketConnected"
        />

        <TeamSummary 
            :team-hp="enemyTeamHp"
            :team-max-hp="enemyTeamMaxHp"
            :chars-remaining="enemyCharsRemaining"
            :total-chars="enemyTotalChars"
            :is-enemy="true"
        />
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
    padding: 0 12px;
    z-index: 1000;
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
</style>
