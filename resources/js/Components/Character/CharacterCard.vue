<!-- Read-only summary tile. All actions (rename, reroll, equip, skills) live in TacticalPanel.
     @spec-link [[ui_character_full_stat_panel]] -->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    character: { type: Object, required: true },
    user:      { type: Object, required: true },
    selected:  { type: Boolean, default: false },
});

const keyStats = computed(() => {
    const c = props.character;
    return [
        { label: 'HP',  value: c.hp       ?? 0 },
        { label: 'ATK', value: c.attack    ?? 0 },
        { label: 'DEF', value: c.defense   ?? 0 },
        { label: 'MOV', value: c.movement  ?? 0 },
    ];
});

const slotIcons = { armor: '◈', weapon: '⚔', utility: '◉' };
const equippedSlots = computed(() => {
    const eq = props.character.equipment ?? {};
    return ['armor', 'weapon', 'utility'].map(slot => ({
        slot,
        icon: slotIcons[slot],
        name: eq[`${slot}_item`]?.shop_item?.name ?? eq[slot]?.shop_item?.name ?? null,
    }));
});
</script>

<template>
    <div
        data-testid="character-card"
        class="p-4 bg-upsilon-gunmetal/30 border hover:border-upsilon-cyan/40 relative group shadow-lg backdrop-blur-md max-h-[320px] overflow-hidden"
        :class="selected ? 'border-upsilon-cyan border-l-2' : 'border-upsilon-steel/20'"
        style="transition: border-color 150ms linear;"
    >
        <!-- Accent corners -->
        <div class="absolute top-0 left-0 w-2 h-2 border-t-2 border-l-2 border-upsilon-cyan/40 group-hover:border-upsilon-cyan transition-colors"></div>
        <div class="absolute bottom-0 right-0 w-2 h-2 border-b-2 border-r-2 border-upsilon-cyan/40 group-hover:border-upsilon-cyan transition-colors"></div>

        <!-- Name + ID -->
        <div class="mb-4">
            <h3 class="font-scifi text-base text-white truncate tracking-widest uppercase">
                {{ character.name }}
            </h3>
            <div class="text-ui-xs text-upsilon-steel font-mono uppercase tracking-[0.2em] mt-0.5">
                SYS {{ character.id.split('-')[0] }}
            </div>
        </div>

        <!-- Key stats (2×2 grid) -->
        <div class="grid grid-cols-4 gap-1 mb-4">
            <div
                v-for="stat in keyStats"
                :key="stat.label"
                class="flex flex-col items-center py-1.5 bg-black/40 border border-upsilon-cyan/10"
            >
                <span class="font-mono text-ui-xs text-upsilon-cyan/60 uppercase">{{ stat.label }}</span>
                <span class="font-scifi text-ui-md text-white font-bold">{{ stat.value }}</span>
            </div>
        </div>

        <!-- Equipment summary -->
        <div class="space-y-0.5">
            <div v-for="slot in equippedSlots" :key="slot.slot" class="flex items-center gap-2">
                <span class="font-mono text-ui-xs text-upsilon-steel/60">{{ slot.icon }}</span>
                <span class="font-mono text-ui-xs truncate"
                    :class="slot.name ? 'text-upsilon-lime' : 'text-upsilon-steel/30'">
                    {{ slot.name ?? 'EMPTY' }}
                </span>
            </div>
        </div>

        <!-- Roulette indicator -->
        <div v-if="character.roulette_available" class="mt-3 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-upsilon-magenta animate-pulse"></span>
            <span class="font-mono text-ui-xs text-upsilon-magenta/70 uppercase tracking-widest">Skill available</span>
        </div>

        <!-- Click hint -->
        <div class="absolute bottom-2 right-3 font-mono text-ui-xs text-upsilon-cyan/20 group-hover:text-upsilon-cyan/60 uppercase tracking-widest transition-colors">
            ›
        </div>
    </div>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
.animate-pulse {
    animation: pulse 2s linear infinite;
}
</style>
