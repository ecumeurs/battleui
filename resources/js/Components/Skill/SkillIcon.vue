<!-- Composable neon-polygon skill icon.
     Major glyph = tags[0], minor overlay = tags[1], tertiary (grade≥III) = tags[2].
     Falls back to behavior-derived tag when tags array is empty.
     @spec-link [[battleui:ui_skill_icon]]
     @spec-link [[shared:req_skill_generation]] -->
<script setup>
import { computed } from 'vue';
import { getIcon } from './skillIconRegistry.js';

const props = defineProps({
    tags:     { type: Array,  default: () => [] },
    grade:    { type: String, default: 'I' },
    size:     { type: Number, default: 32 },
    behavior: { type: String, default: null },
});

const gradeColors = {
    I:   '#4a4a4f',
    II:  '#00f2ff',
    III: '#39ff13',
    IV:  '#ff00ff',
    V:   '#fbbf24',
};

const behaviorFallback = {
    Passive:  ['passive'],
    Counter:  ['counter'],
    Reaction: ['reaction'],
    Trap:     ['trap'],
};

const effectiveTags = computed(() => {
    if (props.tags.length > 0) return props.tags;
    return behaviorFallback[props.behavior] ?? [];
});

const gradeColor  = computed(() => gradeColors[props.grade] ?? gradeColors.I);
const gradeTier   = computed(() => ['III', 'IV', 'V'].includes(props.grade));
const majorIcon   = computed(() => getIcon(effectiveTags.value[0]));
const minorIcon   = computed(() => effectiveTags.value[1] ? getIcon(effectiveTags.value[1]) : null);
const tertiaryIcon = computed(() => gradeTier.value && effectiveTags.value[2] ? getIcon(effectiveTags.value[2]) : null);
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 32 32"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
    >
        <!-- Grade border ring (hexagon outline) -->
        <polygon
            points="16,1 30,8.5 30,23.5 16,31 2,23.5 2,8.5"
            :stroke="gradeColor"
            stroke-width="0.8"
            fill="none"
            opacity="0.5"
        />

        <!-- Major glyph (tags[0]) — full size, 100% opacity -->
        <g v-if="effectiveTags.length > 0"
            :style="`filter: drop-shadow(0 0 2px ${majorIcon.color})`"
        >
            <path
                :d="majorIcon.path"
                :stroke="majorIcon.color"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
                fill="none"
            />
        </g>

        <!-- Minor glyph (tags[1]) — 44% scale, top-right corner, 70% opacity -->
        <g v-if="minorIcon"
            transform="translate(18, 0) scale(0.44)"
            opacity="0.7"
        >
            <path
                :d="minorIcon.path"
                :stroke="minorIcon.color"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                fill="none"
            />
        </g>

        <!-- Tertiary glyph (tags[2], grade ≥ III only) — 31% scale, bottom-right, 50% opacity -->
        <g v-if="tertiaryIcon"
            transform="translate(20, 20) scale(0.31)"
            opacity="0.5"
        >
            <path
                :d="tertiaryIcon.path"
                :stroke="tertiaryIcon.color"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
                fill="none"
            />
        </g>

        <!-- Placeholder when no tags and no behavior fallback -->
        <g v-if="effectiveTags.length === 0" opacity="0.3">
            <path
                d="M16,4 L16,28 M4,16 L28,16"
                stroke="#4a4a4f"
                stroke-width="1.5"
                stroke-linecap="round"
            />
        </g>
    </svg>
</template>
