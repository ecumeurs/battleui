<!-- Action Panel for combat turn controls -->
<script setup>
const props = defineProps({
    isPlayerTurn: { type: Boolean, default: true },
    isProcessing: { type: Boolean, default: false },
    canMove: { type: Boolean, default: true },
    canAttack: { type: Boolean, default: true },
    moveCostPerTile: { type: Number, default: 20 },
    attackCost: { type: Number, default: 100 },
    passCost: { type: Number, default: 300 },
    selectedAction: { type: String, default: null },
});

const emit = defineEmits(['action']);
</script>

<template>
    <div class="action-panel" :class="{ 'action-panel--disabled': !isPlayerTurn || isProcessing }">
        <button
            class="action-btn action-btn--move"
            :class="{ 'action-btn--selected': selectedAction === 'move' }"
            :disabled="!isPlayerTurn || !canMove"
            @click="emit('action', 'move')"
        >
            <span class="action-btn__icon">⬡</span>
            <span class="action-btn__label">MOVE</span>
            <span class="action-btn__cost">+{{ moveCostPerTile }}/tile</span>
        </button>

        <button
            class="action-btn action-btn--attack"
            :class="{ 'action-btn--selected': selectedAction === 'attack' }"
            :disabled="!isPlayerTurn || !canAttack"
            @click="emit('action', 'attack')"
        >
            <span class="action-btn__icon">⚔</span>
            <span class="action-btn__label">ATTACK</span>
            <span class="action-btn__cost">+{{ attackCost }}</span>
        </button>

        <button
            class="action-btn action-btn--pass"
            :disabled="!isPlayerTurn"
            @click="emit('action', 'pass')"
        >
            <span class="action-btn__icon">⏭</span>
            <span class="action-btn__label">PASS</span>
            <span class="action-btn__cost">+{{ passCost }}</span>
        </button>

        <div class="action-panel__divider"></div>

        <button
            class="action-btn action-btn--forfeit"
            :disabled="!isPlayerTurn"
            @click="emit('action', 'forfeit')"
        >
            <span class="action-btn__icon">⚠</span>
            <span class="action-btn__label">FORFEIT</span>
        </button>
    </div>
</template>

<style scoped>
.action-panel {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: rgba(10, 10, 11, 0.6);
    border-top: 1px solid rgba(74, 74, 79, 0.2);
    justify-content: center;
}

.action-panel--disabled {
    opacity: 0.4;
    pointer-events: none;
}

.action-panel__divider {
    width: 1px;
    height: 28px;
    background: rgba(74, 74, 79, 0.3);
    margin: 0 6px;
}

.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    padding: 6px 14px;
    background: rgba(26, 26, 30, 0.8);
    border: 1px solid rgba(74, 74, 79, 0.3);
    color: #e0e0e0;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    font-family: inherit;
}

.action-btn:hover:not(:disabled) {
    border-color: rgba(0, 242, 255, 0.5);
    background: rgba(26, 26, 30, 1);
}

.action-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.action-btn__icon {
    font-size: 14px;
}

.action-btn__label {
    font-family: 'Orbitron', sans-serif;
    font-size: 8px;
    letter-spacing: 0.2em;
    text-transform: uppercase;
}

.action-btn__cost {
    font-family: 'JetBrains Mono', monospace;
    font-size: 7px;
    color: rgba(0, 242, 255, 0.5);
}

/* Action-specific colors */
.action-btn--move:hover:not(:disabled) {
    border-color: #00f2ff;
    box-shadow: 0 0 8px rgba(0, 242, 255, 0.3);
}

.action-btn--move:hover:not(:disabled) .action-btn__icon {
    color: #00f2ff;
}

.action-btn--attack:hover:not(:disabled) {
    border-color: #ff00ff;
    box-shadow: 0 0 8px rgba(255, 0, 255, 0.3);
}

.action-btn--attack:hover:not(:disabled) .action-btn__icon {
    color: #ff00ff;
}

.action-btn--pass:hover:not(:disabled) {
    border-color: #ff8c00;
    box-shadow: 0 0 8px rgba(255, 140, 0, 0.3);
}

.action-btn--pass:hover:not(:disabled) .action-btn__icon {
    color: #ff8c00;
}

.action-btn--forfeit {
    border-color: rgba(255, 32, 32, 0.2);
}

.action-btn--forfeit:hover:not(:disabled) {
    border-color: #ff2020;
    box-shadow: 0 0 8px rgba(255, 32, 32, 0.3);
    background: rgba(255, 32, 32, 0.05);
}

.action-btn--forfeit:hover:not(:disabled) .action-btn__icon {
    color: #ff2020;
}

.action-btn--move.action-btn--selected {
    background: rgba(0, 242, 255, 0.15);
    border-color: #00f2ff;
    box-shadow: 0 0 8px rgba(0, 242, 255, 0.4);
}
.action-btn--move.action-btn--selected .action-btn__icon {
    color: #00f2ff;
}

.action-btn--attack.action-btn--selected {
    background: rgba(255, 0, 255, 0.15);
    border-color: #ff00ff;
    box-shadow: 0 0 8px rgba(255, 0, 255, 0.4);
}
.action-btn--attack.action-btn--selected .action-btn__icon {
    color: #ff00ff;
}
</style>
