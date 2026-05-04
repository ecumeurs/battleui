<!-- One skill slot in a character's loadout. Shows equipped skill name or "EMPTY".
     Click emits @click so the parent can open swap UI.
     @spec-link [[rule_character_skill_slots]] -->
<script setup>
import SkillIcon from './SkillIcon.vue';

defineProps({
    slot: {
        type: Number,
        required: true,
    },
    skill: {
        type: Object,
        default: null,
    },
    selected: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['click']);

function getData(skill) {
    return skill?.instance_data ?? skill;
}

const gradeColors = {
    I:   'text-upsilon-steel',
    II:  'text-upsilon-cyan',
    III: 'text-upsilon-lime',
    IV:  'text-upsilon-magenta',
    V:   'text-amber-400',
};
</script>

<template>
    <button
        @click="$emit('click', slot)"
        class="w-full flex items-center gap-3 px-3 py-2 border transition-all duration-150 group"
        :class="selected
            ? 'bg-upsilon-cyan/10 border-upsilon-cyan/60'
            : skill
                ? 'bg-black/40 border-upsilon-steel/30 hover:border-upsilon-cyan/40'
                : 'bg-black/20 border-dashed border-upsilon-steel/20 hover:border-upsilon-steel/40'"
    >
        <!-- Slot index -->
        <span class="shrink-0 font-scifi text-[8px] text-upsilon-steel uppercase w-5 text-center">
            {{ slot + 1 }}
        </span>

        <div v-if="skill" class="flex-1 flex items-center gap-2 min-w-0">
            <SkillIcon
                :tags="getData(skill).tags ?? []"
                :grade="getData(skill).grade ?? 'I'"
                :behavior="getData(skill).behavior"
                :size="16"
                class="shrink-0"
            />
            <span class="font-scifi text-[10px] uppercase tracking-widest truncate text-white">
                {{ getData(skill).name }}
            </span>
            <span class="shrink-0 font-scifi text-[8px]"
                :class="gradeColors[getData(skill).grade] ?? 'text-upsilon-steel'">
                {{ getData(skill).grade }}
            </span>
        </div>

        <div v-else class="flex-1">
            <span class="font-mono text-[8px] text-upsilon-steel/40 uppercase tracking-widest">EMPTY SLOT</span>
        </div>

        <span class="shrink-0 text-upsilon-steel/40 group-hover:text-upsilon-cyan/60 transition-colors text-xs">›</span>
    </button>
</template>
