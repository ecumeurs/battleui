<!-- @spec-link [[ui_action_panel]] -->
<!-- @spec-link [[mech_action_economy]] -->
<!-- Action Panel — turn-aware combat action dispatcher -->
<script setup>
const props = defineProps({
    isPlayerTurn:    { type: Boolean, default: false },
    isProcessing:    { type: Boolean, default: false },
    canMove:         { type: Boolean, default: true },
    canAttack:       { type: Boolean, default: true },
    moveCostPerTile: { type: Number,  default: 20 },
    attackCost:      { type: Number,  default: 100 },
    passCost:        { type: Number,  default: 300 },
    selectedAction:  { type: String,  default: null },
    /** The entity currently acting — used for context display */
    activeCharacter: { type: Object,  default: null },
    /** Name of the player currently holding the turn */
    activePlayerName:{ type: String,  default: '' },
});

const emit = defineEmits(['action']);

function fire(type) {
    if (!props.isPlayerTurn || props.isProcessing) return;
    emit('action', type);
}
</script>

<template>
    <!-- ── Outer Box ─────────────────────────────────────────── -->
    <div class="ap-box" :class="{ 'ap-box--active': isPlayerTurn, 'ap-box--waiting': !isPlayerTurn }">

        <!-- ── Header bar ───────────────────────────────────── -->
        <div class="ap-header">
            <!-- Left: status pill -->
            <div class="ap-status" :class="isPlayerTurn ? 'ap-status--yours' : 'ap-status--waiting'">
                <span class="ap-status__dot"></span>
                <span class="ap-status__text">
                    {{ isPlayerTurn ? 'YOUR TURN' : 'WAITING' }}
                </span>
            </div>

            <!-- Center: active character context -->
            <div class="ap-context">
                <template v-if="activeCharacter">
                    <span class="ap-context__name">{{ activeCharacter.name }}</span>
                    <span class="ap-context__sep">·</span>
                    <span class="ap-context__hp">HP {{ activeCharacter.hp }}/{{ activeCharacter.max_hp }}</span>
                    <span class="ap-context__sep">·</span>
                    <span class="ap-context__mv">MOV {{ activeCharacter.move }}/{{ activeCharacter.max_move }}</span>
                </template>
                <template v-else>
                    <span class="ap-context__placeholder">— no entity acting —</span>
                </template>
            </div>

            <!-- Right: who owns this turn -->
            <div class="ap-owner">
                <span v-if="!isPlayerTurn && activePlayerName" class="ap-owner__label">
                    {{ activePlayerName }}'s turn
                </span>
                <span v-else-if="isProcessing" class="ap-owner__label ap-owner__label--processing">
                    ⬡ Sending…
                </span>
            </div>
        </div>

        <!-- ── Divider ───────────────────────────────────────── -->
        <div class="ap-divider"></div>

        <!-- ── Buttons ───────────────────────────────────────── -->
        <div class="ap-buttons" :class="{ 'ap-buttons--locked': !isPlayerTurn || isProcessing }">

            <!-- MOVE -->
            <button
                class="ap-btn ap-btn--move"
                :class="{ 'ap-btn--selected': selectedAction === 'move' }"
                :disabled="!isPlayerTurn || !canMove || isProcessing"
                @click="fire('move')"
            >
                <span class="ap-btn__icon">⬡</span>
                <span class="ap-btn__label">MOVE</span>
                <span class="ap-btn__cost">+{{ moveCostPerTile }}/tile</span>
            </button>

            <!-- ATTACK -->
            <button
                class="ap-btn ap-btn--attack"
                :class="{ 'ap-btn--selected': selectedAction === 'attack' }"
                :disabled="!isPlayerTurn || !canAttack || isProcessing"
                @click="fire('attack')"
            >
                <span class="ap-btn__icon">⚔</span>
                <span class="ap-btn__label">ATTACK</span>
                <span class="ap-btn__cost">+{{ attackCost }}</span>
            </button>

            <!-- PASS -->
            <button
                class="ap-btn ap-btn--pass"
                :disabled="!isPlayerTurn || isProcessing"
                @click="fire('pass')"
            >
                <span class="ap-btn__icon">⏭</span>
                <span class="ap-btn__label">PASS</span>
                <span class="ap-btn__cost">+{{ passCost }}</span>
            </button>

            <!-- Divider -->
            <div class="ap-btn-sep"></div>

            <!-- FORFEIT -->
            <button
                class="ap-btn ap-btn--forfeit"
                :disabled="!isPlayerTurn || isProcessing"
                @click="fire('forfeit')"
            >
                <span class="ap-btn__icon">⚠</span>
                <span class="ap-btn__label">FORFEIT</span>
            </button>

        </div>

        <!-- ── Waiting overlay (shown when not player's turn) ── -->
        <transition name="ap-lock">
            <div v-if="!isPlayerTurn" class="ap-lock-overlay" aria-hidden="true">
                <span class="ap-lock-overlay__icon">🔒</span>
                <span class="ap-lock-overlay__text">Actions locked — awaiting your turn</span>
            </div>
        </transition>

    </div>
</template>

<style scoped>
/* ── Box wrapper ──────────────────────────────────────────────── */
.ap-box {
    position: relative;
    display: flex;
    flex-direction: column;
    background: rgba(10, 10, 12, 0.75);
    border: 1px solid rgba(74, 74, 79, 0.25);
    border-radius: 2px;
    overflow: hidden;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.ap-box--active {
    border-color: rgba(0, 242, 255, 0.35);
    box-shadow:
        0 0 0 1px rgba(0, 242, 255, 0.08),
        inset 0 0 24px rgba(0, 242, 255, 0.04);
}

.ap-box--waiting {
    border-color: rgba(74, 74, 79, 0.18);
    box-shadow: none;
}

/* ── Header ───────────────────────────────────────────────────── */
.ap-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 10px;
    background: rgba(16, 16, 20, 0.8);
}

/* Status pill */
.ap-status {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 2px 8px;
    border-radius: 2px;
    flex-shrink: 0;
}

.ap-status--yours {
    background: rgba(0, 242, 255, 0.1);
    border: 1px solid rgba(0, 242, 255, 0.3);
}

.ap-status--waiting {
    background: rgba(74, 74, 79, 0.12);
    border: 1px solid rgba(74, 74, 79, 0.2);
}

.ap-status__dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}

.ap-status--yours .ap-status__dot {
    background: #00f2ff;
    box-shadow: 0 0 6px #00f2ff;
    animation: ap-pulse 1.4s ease-in-out infinite;
}

.ap-status--waiting .ap-status__dot {
    background: rgba(74, 74, 79, 0.6);
}

.ap-status__text {
    font-family: 'Orbitron', sans-serif;
    font-size: 8px;
    letter-spacing: 0.2em;
}

.ap-status--yours .ap-status__text   { color: #00f2ff; }
.ap-status--waiting .ap-status__text { color: rgba(224, 224, 224, 0.6); }

/* Context info */
.ap-context {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 6px;
    overflow: hidden;
}

.ap-context__name {
    font-family: 'Orbitron', sans-serif;
    font-size: 9px;
    letter-spacing: 0.15em;
    color: #e0e0e0;
    white-space: nowrap;
}

.ap-context__sep {
    color: rgba(224, 224, 224, 0.4);
    font-size: 9px;
}

.ap-context__hp,
.ap-context__mv {
    font-family: 'JetBrains Mono', monospace;
    font-size: 9px;
    color: rgba(0, 242, 255, 0.45);
    white-space: nowrap;
}

.ap-context__placeholder {
    font-family: 'JetBrains Mono', monospace;
    font-size: 9px;
    color: rgba(224, 224, 224, 0.4);
    font-style: italic;
}

/* Owner label */
.ap-owner {
    flex-shrink: 0;
}

.ap-owner__label {
    font-family: 'JetBrains Mono', monospace;
    font-size: 8px;
    color: rgba(224, 224, 224, 0.5);
    letter-spacing: 0.05em;
    white-space: nowrap;
}

.ap-owner__label--processing {
    color: rgba(255, 140, 0, 0.7);
    animation: ap-blink 0.8s step-end infinite;
}

/* ── Thin divider ─────────────────────────────────────────────── */
.ap-divider {
    height: 1px;
    background: rgba(74, 74, 79, 0.15);
}

/* ── Buttons row ─────────────────────────────────────────────── */
.ap-buttons {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 6px 10px;
    transition: opacity 0.3s ease, filter 0.3s ease;
}

.ap-buttons--locked {
    opacity: 0.28;
    filter: saturate(0.3) brightness(0.7);
    pointer-events: none;
}

.ap-btn-sep {
    width: 1px;
    height: 26px;
    background: rgba(74, 74, 79, 0.25);
    margin: 0 4px;
}

/* Base button */
.ap-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    padding: 6px 14px;
    background: rgba(22, 22, 28, 0.8);
    border: 1px solid rgba(74, 74, 79, 0.25);
    border-radius: 2px;
    color: #e0e0e0;
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    font-family: inherit;
    flex-shrink: 0;
}

.ap-btn:hover:not(:disabled) {
    background: rgba(30, 30, 38, 1);
}

.ap-btn:disabled {
    cursor: not-allowed;
}

.ap-btn__icon  { font-size: 13px; }

.ap-btn__label {
    font-family: 'Orbitron', sans-serif;
    font-size: 7px;
    letter-spacing: 0.2em;
    text-transform: uppercase;
}

.ap-btn__cost {
    font-family: 'JetBrains Mono', monospace;
    font-size: 7px;
    color: rgba(0, 242, 255, 0.4);
}

/* Move */
.ap-btn--move:hover:not(:disabled) {
    border-color: #00f2ff;
    box-shadow: 0 0 8px rgba(0, 242, 255, 0.3);
}
.ap-btn--move:hover:not(:disabled) .ap-btn__icon { color: #00f2ff; }

.ap-btn--move.ap-btn--selected {
    background: rgba(0, 242, 255, 0.12);
    border-color: #00f2ff;
    box-shadow: 0 0 10px rgba(0, 242, 255, 0.35);
}
.ap-btn--move.ap-btn--selected .ap-btn__icon { color: #00f2ff; }

/* Attack */
.ap-btn--attack:hover:not(:disabled) {
    border-color: #ff00ff;
    box-shadow: 0 0 8px rgba(255, 0, 255, 0.3);
}
.ap-btn--attack:hover:not(:disabled) .ap-btn__icon { color: #ff00ff; }

.ap-btn--attack.ap-btn--selected {
    background: rgba(255, 0, 255, 0.12);
    border-color: #ff00ff;
    box-shadow: 0 0 10px rgba(255, 0, 255, 0.35);
}
.ap-btn--attack.ap-btn--selected .ap-btn__icon { color: #ff00ff; }

/* Pass */
.ap-btn--pass:hover:not(:disabled) {
    border-color: #ff8c00;
    box-shadow: 0 0 8px rgba(255, 140, 0, 0.3);
}
.ap-btn--pass:hover:not(:disabled) .ap-btn__icon { color: #ff8c00; }

/* Forfeit */
.ap-btn--forfeit {
    border-color: rgba(255, 32, 32, 0.18);
}
.ap-btn--forfeit:hover:not(:disabled) {
    border-color: #ff2020;
    box-shadow: 0 0 8px rgba(255, 32, 32, 0.3);
    background: rgba(255, 32, 32, 0.06);
}
.ap-btn--forfeit:hover:not(:disabled) .ap-btn__icon { color: #ff2020; }

/* ── Lock overlay ────────────────────────────────────────────── */
.ap-lock-overlay {
    position: absolute;
    /* cover only the buttons row area, not the header */
    left: 0;
    right: 0;
    bottom: 0;
    top: 32px; /* roughly header + divider height */
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: rgba(10, 10, 12, 0.0); /* transparent — colour comes from ap-buttons--locked */
    pointer-events: none;
}

.ap-lock-overlay__icon {
    font-size: 10px;
    opacity: 0.3;
}

.ap-lock-overlay__text {
    font-family: 'JetBrains Mono', monospace;
    font-size: 8px;
    letter-spacing: 0.12em;
    color: rgba(224, 224, 224, 0.6);
    text-transform: uppercase;
}

/* ── Transitions ─────────────────────────────────────────────── */
.ap-lock-enter-active,
.ap-lock-leave-active { transition: opacity 0.3s ease; }
.ap-lock-enter-from,
.ap-lock-leave-to     { opacity: 0; }

/* ── Keyframes ───────────────────────────────────────────────── */
@keyframes ap-pulse {
    0%, 100% { opacity: 1; box-shadow: 0 0 6px #00f2ff; }
    50%       { opacity: 0.4; box-shadow: 0 0 3px #00f2ff; }
}

@keyframes ap-blink {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.35; }
}
</style>
