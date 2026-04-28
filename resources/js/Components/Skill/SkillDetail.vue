<!-- Full skill property breakdown. Used in character modal + roulette reveal.
     Reads either a CharacterSkill (with instance_data) or a bare skill object.
     @spec-link [[entity_character_skill_inventory]] -->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    skill: {
        type: Object,
        required: true,
    },
});

const gradeStyles = {
    I:   'bg-upsilon-steel/20 text-upsilon-steel border-upsilon-steel/40',
    II:  'bg-upsilon-cyan/20 text-upsilon-cyan border-upsilon-cyan/40',
    III: 'bg-upsilon-lime/20 text-upsilon-lime border-upsilon-lime/40',
    IV:  'bg-upsilon-magenta/20 text-upsilon-magenta border-upsilon-magenta/40',
    V:   'bg-amber-400/20 text-amber-400 border-amber-400/40',
};

const behaviorColors = {
    Direct:   'text-upsilon-cyan',
    Reaction: 'text-upsilon-lime',
    Passive:  'text-upsilon-steel',
    Counter:  'text-upsilon-magenta',
    Trap:     'text-amber-400',
};

const data = computed(() => props.skill.instance_data ?? props.skill);
</script>

<template>
    <div class="space-y-5">
        <!-- Header -->
        <div>
            <div class="flex items-center gap-3 mb-2">
                <span class="px-2 py-0.5 border font-scifi text-[9px] font-bold"
                    :class="gradeStyles[data.grade] ?? gradeStyles.I">
                    GRADE {{ data.grade ?? 'I' }}
                </span>
                <span class="font-mono text-[9px] uppercase"
                    :class="behaviorColors[data.behavior] ?? 'text-upsilon-steel'">
                    {{ data.behavior ?? '—' }}
                </span>
            </div>
            <h3 class="font-scifi text-xl font-bold text-white uppercase tracking-wider">
                {{ data.name }}
            </h3>
        </div>

        <div class="border-t border-upsilon-steel/20"></div>

        <!-- Targeting -->
        <div v-if="data.targeting && Object.keys(data.targeting).length">
            <div class="text-[8px] font-mono text-upsilon-steel uppercase tracking-[0.4em] mb-2">TARGETING</div>
            <div class="space-y-1">
                <div v-for="(v, k) in data.targeting" :key="k"
                    class="flex justify-between px-3 py-1.5 bg-black/40 border border-upsilon-steel/10">
                    <span class="font-mono text-[9px] text-upsilon-steel uppercase tracking-widest">{{ k }}</span>
                    <span class="font-scifi text-[10px] text-upsilon-cyan font-bold">{{ v }}</span>
                </div>
            </div>
        </div>

        <!-- Costs -->
        <div v-if="data.costs && Object.keys(data.costs).length">
            <div class="text-[8px] font-mono text-upsilon-steel uppercase tracking-[0.4em] mb-2">ACTIVATION COST</div>
            <div class="space-y-1">
                <div v-for="(v, k) in data.costs" :key="k"
                    class="flex justify-between px-3 py-1.5 bg-black/40 border border-upsilon-steel/10">
                    <span class="font-mono text-[9px] text-upsilon-steel uppercase tracking-widest">{{ k }}</span>
                    <span class="font-scifi text-[10px] text-upsilon-magenta font-bold">{{ v }}</span>
                </div>
            </div>
        </div>

        <!-- Effect -->
        <div v-if="data.effect && Object.keys(data.effect).length">
            <div class="text-[8px] font-mono text-upsilon-steel uppercase tracking-[0.4em] mb-2">EFFECT</div>
            <div class="space-y-1">
                <div v-for="(v, k) in data.effect" :key="k"
                    class="flex justify-between px-3 py-1.5 bg-black/40 border border-upsilon-steel/10">
                    <span class="font-mono text-[9px] text-upsilon-steel uppercase tracking-widest">{{ k }}</span>
                    <span class="font-scifi text-[10px] text-upsilon-lime font-bold">{{ v }}</span>
                </div>
            </div>
        </div>

        <!-- Weight indicators -->
        <div v-if="data.weight_positive !== undefined" class="border-t border-upsilon-steel/20 pt-4">
            <div class="text-[8px] font-mono text-upsilon-steel uppercase tracking-[0.4em] mb-2">WEIGHT ANALYSIS</div>
            <div class="flex justify-between font-mono text-[8px] uppercase">
                <span class="text-upsilon-lime">+{{ data.weight_positive ?? 0 }} POSITIVE</span>
                <span class="text-upsilon-magenta">-{{ data.weight_negative ?? 0 }} NEGATIVE</span>
            </div>
        </div>
    </div>
</template>
