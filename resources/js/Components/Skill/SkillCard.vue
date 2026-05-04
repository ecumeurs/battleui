<!-- Compact skill card for list contexts. Shows grade pill, behavior, name, equipped flag.
     @spec-link [[entity_character_skill_inventory]] -->
<script setup>
import SkillIcon from './SkillIcon.vue';

defineProps({
    skill: {
        type: Object,
        required: true,
    },
    equipped: {
        type: Boolean,
        default: false,
    },
    selectable: {
        type: Boolean,
        default: false,
    },
    selected: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['click']);

// Grade pill: I=steel II=cyan III=lime IV=magenta V=gold
const gradeStyles = {
    I:   'bg-upsilon-steel/20 text-upsilon-steel border-upsilon-steel/40',
    II:  'bg-upsilon-cyan/20 text-upsilon-cyan border-upsilon-cyan/40',
    III: 'bg-upsilon-lime/20 text-upsilon-lime border-upsilon-lime/40',
    IV:  'bg-upsilon-magenta/20 text-upsilon-magenta border-upsilon-magenta/40',
    V:   'bg-amber-400/20 text-amber-400 border-amber-400/40',
};

// Behavior icon glyphs
const behaviorIcons = {
    Direct:   '→',
    Reaction: '⊘',
    Passive:  '∞',
    Counter:  '⇄',
    Trap:     '△',
};

function getData(skill) {
    return skill.instance_data ?? skill;
}
</script>

<template>
    <button
        @click="$emit('click', skill)"
        class="w-full text-left px-3 py-2.5 border transition-all duration-150"
        :class="selected
            ? 'bg-upsilon-magenta/10 border-upsilon-magenta/50'
            : selectable
                ? 'bg-black/30 border-upsilon-steel/20 hover:border-upsilon-cyan/30 hover:bg-upsilon-gunmetal/40'
                : 'bg-black/30 border-upsilon-steel/20 cursor-default'"
    >
        <div class="flex items-center gap-3">
            <!-- Grade pill -->
            <span
                class="shrink-0 px-1.5 py-0.5 border font-scifi text-[8px] font-bold"
                :class="gradeStyles[getData(skill).grade] ?? gradeStyles.I"
            >
                {{ getData(skill).grade ?? 'I' }}
            </span>

            <!-- Skill icon -->
            <SkillIcon
                :tags="getData(skill).tags ?? []"
                :grade="getData(skill).grade ?? 'I'"
                :behavior="getData(skill).behavior"
                :size="20"
                class="shrink-0"
            />

            <!-- Name -->
            <div class="flex-1 min-w-0">
                <div class="font-scifi text-[10px] text-white uppercase tracking-widest truncate">
                    {{ getData(skill).name }}
                </div>
                <div class="font-mono text-[7px] text-upsilon-steel uppercase">
                    {{ getData(skill).behavior ?? '—' }}
                </div>
            </div>

            <!-- Equipped badge -->
            <span v-if="equipped" class="shrink-0 font-mono text-[7px] text-upsilon-lime uppercase tracking-widest">
                ● ACTIVE
            </span>
        </div>
    </button>
</template>
