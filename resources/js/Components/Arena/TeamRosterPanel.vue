<!-- @spec-link [[ui_team_roster_panel]] -->
<script setup>
import CharacterBattleCard from './CharacterBattleCard.vue';

const props = defineProps({
    players: { type: Array, required: true },
    /** player_id of the player whose characters get detailed view */
    detailedPlayerId: { type: String, default: '' },
    side: { type: String, default: 'left' }, // 'left' or 'right'
    teamColors: { type: Object, default: () => ({}) },
});
</script>

<template>
    <div class="roster-panel" :class="'roster-panel--' + side">
        <div class="roster-panel__header">
            <span class="roster-panel__header-icon">◆</span>
            {{ side === 'left' ? 'ALLIED FORCES' : 'HOSTILE FORCES' }}
        </div>

        <div v-for="player in players" :key="player.id" class="roster-panel__group">
            <div class="roster-panel__player-name" :style="{ color: teamColors[player.id] || '#00f2ff' }">
                <span class="roster-panel__player-icon">▸</span>
                {{ player.nickname }}
            </div>

            <CharacterBattleCard
                v-for="char in player.entities"
                :key="char.id"
                :character="char"
                :compact="player.id !== detailedPlayerId"
                :accent-color="teamColors[player.id] || '#00f2ff'"
                :is-active="char._isActive || false"
            />
        </div>
    </div>
</template>

<style scoped>
.roster-panel {
    width: 220px;
    min-width: 220px;
    background: rgba(10, 10, 11, 0.5);
    border: 1px solid rgba(74, 74, 79, 0.3);
    backdrop-filter: blur(4px);
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    max-height: 100%;
}

.roster-panel--left {
    border-right: 1px solid rgba(0, 242, 255, 0.15);
}

.roster-panel--right {
    border-left: 1px solid rgba(255, 0, 255, 0.15);
}

.roster-panel__header {
    font-family: 'Orbitron', sans-serif;
    font-size: 9px;
    letter-spacing: 0.3em;
    text-transform: uppercase;
    color: rgba(0, 242, 255, 0.5);
    padding: 10px 12px 6px;
    border-bottom: 1px solid rgba(74, 74, 79, 0.2);
    display: flex;
    align-items: center;
    gap: 6px;
}

.roster-panel__header-icon {
    font-size: 6px;
    color: rgba(0, 242, 255, 0.5);
}

.roster-panel__group {
    padding: 6px 8px;
    border-bottom: 1px solid rgba(74, 74, 79, 0.1);
}

.roster-panel__group:last-child {
    border-bottom: none;
}

.roster-panel__player-name {
    font-family: 'Orbitron', sans-serif;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    padding: 4px 4px 6px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.roster-panel__player-icon {
    font-size: 8px;
}

/* Scrollbar styling */
.roster-panel::-webkit-scrollbar {
    width: 3px;
}
.roster-panel::-webkit-scrollbar-track {
    background: rgba(10, 10, 11, 0.3);
}
.roster-panel::-webkit-scrollbar-thumb {
    background: rgba(74, 74, 79, 0.4);
}
</style>
