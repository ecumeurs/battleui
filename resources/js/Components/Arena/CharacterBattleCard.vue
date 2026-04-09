<!-- @spec-link [[ui_character_battle_card]] -->
<script setup>
const props = defineProps({
    character: { type: Object, required: true },
    compact: { type: Boolean, default: false },
    accentColor: { type: String, default: '#00f2ff' },
    isActive: { type: Boolean, default: false },
});

function hpPercent() {
    if (!props.character.max_hp) return 0;
    return Math.round((props.character.hp / props.character.max_hp) * 100);
}

function movePercent() {
    if (!props.character.max_move) return 0;
    return Math.round((props.character.move / props.character.max_move) * 100);
}

function hpBarColor() {
    const pct = hpPercent();
    if (pct > 60) return '#39ff13';
    if (pct > 30) return '#ff8c00';
    return '#ff2020';
}
</script>

<template>
    <div
        class="char-card"
        :class="{ 'char-card--compact': compact, 'char-card--active': isActive }"
        :style="{ '--accent': accentColor }"
    >
        <div class="char-card__name">
            <span class="char-card__indicator" :style="{ background: accentColor }"></span>
            {{ character.name }}
        </div>

        <!-- HP Bar -->
        <div class="char-card__bar-row">
            <span class="char-card__bar-label">HP</span>
            <div class="char-card__bar-track">
                <div
                    class="char-card__bar-fill"
                    :style="{ width: hpPercent() + '%', background: hpBarColor(), boxShadow: '0 0 8px ' + hpBarColor() }"
                ></div>
            </div>
            <span class="char-card__bar-value">{{ character.hp }}/{{ character.max_hp }}</span>
        </div>

        <!-- Extended stats (non-compact) -->
        <template v-if="!compact">
            <!-- Movement Bar -->
            <div class="char-card__bar-row">
                <span class="char-card__bar-label">MV</span>
                <div class="char-card__bar-track">
                    <div
                        class="char-card__bar-fill"
                        :style="{ width: movePercent() + '%', background: accentColor, boxShadow: '0 0 8px ' + accentColor }"
                    ></div>
                </div>
                <span class="char-card__bar-value">{{ character.move }}/{{ character.max_move }}</span>
            </div>

            <div class="char-card__stats">
                <div class="char-card__stat">
                    <span class="char-card__stat-label">ATK</span>
                    <span class="char-card__stat-value">{{ character.attack }}</span>
                </div>
                <div class="char-card__stat">
                    <span class="char-card__stat-label">DEF</span>
                    <span class="char-card__stat-value">{{ character.defense }}</span>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.char-card {
    background: rgba(10, 10, 11, 0.7);
    border: 1px solid rgba(74, 74, 79, 0.4);
    border-left: 2px solid var(--accent);
    padding: 8px 10px;
    margin-bottom: 4px;
    font-family: 'JetBrains Mono', monospace;
    transition: all 0.2s ease;
}

.char-card--active {
    border-color: var(--accent);
    background: rgba(10, 10, 11, 0.9);
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent) 30%, transparent);
}

.char-card--compact {
    padding: 5px 8px;
}

.char-card__name {
    display: flex;
    align-items: center;
    gap: 6px;
    font-family: 'Orbitron', sans-serif;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #e0e0e0;
    margin-bottom: 4px;
}

.char-card__indicator {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
    box-shadow: 0 0 6px currentColor;
}

.char-card__bar-row {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 3px;
}

.char-card__bar-label {
    font-size: 8px;
    color: rgba(0, 242, 255, 0.5);
    text-transform: uppercase;
    width: 18px;
    flex-shrink: 0;
}

.char-card__bar-track {
    flex: 1;
    height: 6px;
    background: rgba(26, 26, 30, 0.8);
    border: 1px solid rgba(74, 74, 79, 0.3);
    overflow: hidden;
}

.char-card__bar-fill {
    height: 100%;
    transition: width 0.5s ease;
}

.char-card__bar-value {
    font-size: 8px;
    color: rgba(224, 224, 224, 0.6);
    width: 40px;
    text-align: right;
    flex-shrink: 0;
}

.char-card__stats {
    display: flex;
    gap: 12px;
    margin-top: 4px;
}

.char-card__stat {
    display: flex;
    align-items: center;
    gap: 4px;
}

.char-card__stat-label {
    font-size: 8px;
    color: rgba(0, 242, 255, 0.5);
    text-transform: uppercase;
}

.char-card__stat-value {
    font-size: 10px;
    color: var(--accent);
    font-family: 'Orbitron', sans-serif;
}
</style>
