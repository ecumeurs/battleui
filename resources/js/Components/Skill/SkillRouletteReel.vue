<!-- One spinning reel. Cycles through skill names fast while spinning;
     decelerates and lands on a target name on stop().
     @spec-link [[entity_character_skill_inventory]] -->
<script setup>
import { ref, onUnmounted } from 'vue';

const props = defineProps({
    names: {
        type: Array,
        default: () => [],
    },
    isWinner: {
        type: Boolean,
        default: false,
    },
});

// State: 'idle' | 'spinning' | 'stopping' | 'done'
const state = ref('idle');
const displayName = ref('');
const landedName = ref('');

let interval = null;
let stopIndex = 0;
let spinCount = 0;

function start(initialNames) {
    state.value = 'spinning';
    landedName.value = '';
    const pool = initialNames?.length ? initialNames : ['---'];
    spinCount = 0;
    interval = setInterval(() => {
        const i = Math.floor(Math.random() * pool.length);
        displayName.value = pool[i];
        spinCount++;
    }, 80);
}

function stop(target) {
    if (state.value !== 'spinning') return;
    state.value = 'stopping';
    clearInterval(interval);

    // Decelerate over a short sequence then land
    const pool = props.names?.length ? props.names : ['---'];
    const steps = 6;
    let step = 0;
    const decel = setInterval(() => {
        step++;
        const delay = 80 + step * 40;
        if (step >= steps) {
            clearInterval(decel);
            displayName.value = target ?? pool[Math.floor(Math.random() * pool.length)];
            landedName.value = displayName.value;
            state.value = 'done';
        } else {
            displayName.value = pool[Math.floor(Math.random() * pool.length)];
        }
    }, 120);
}

function reset() {
    clearInterval(interval);
    state.value = 'idle';
    displayName.value = '';
    landedName.value = '';
}

onUnmounted(() => clearInterval(interval));

defineExpose({ start, stop, reset });
</script>

<template>
    <div
        class="reel relative flex flex-col items-center justify-center w-48 h-32 border-2 overflow-hidden select-none transition-all duration-500"
        :class="state === 'done' && isWinner
            ? 'border-upsilon-magenta shadow-glow-winner'
            : state === 'done'
                ? 'border-upsilon-steel/40'
                : 'border-upsilon-cyan/40'"
    >
        <!-- Corner accents on winner -->
        <template v-if="state === 'done' && isWinner">
            <div class="absolute -top-px -left-px w-3 h-3 border-t-2 border-l-2 border-upsilon-magenta"></div>
            <div class="absolute -bottom-px -right-px w-3 h-3 border-b-2 border-r-2 border-upsilon-magenta"></div>
        </template>

        <!-- Background scan-line texture -->
        <div class="absolute inset-0 scanlines opacity-10 pointer-events-none"></div>

        <!-- Spinning / idle content -->
        <div v-if="state === 'idle'" class="font-mono text-ui-xs text-upsilon-steel/50 uppercase tracking-widest">
            STANDBY
        </div>

        <div v-else
            class="px-3 text-center transition-all duration-100"
            :class="state === 'spinning' ? 'blur-[0.5px]' : ''"
        >
            <div
                class="font-scifi text-ui-md uppercase tracking-widest transition-colors duration-300"
                :class="state === 'done' && isWinner ? 'text-upsilon-magenta neon-text' : 'text-white'"
            >
                {{ displayName }}
            </div>
        </div>

        <!-- Winner crown overlay -->
        <div v-if="state === 'done' && isWinner"
            class="absolute top-1 right-2 font-mono text-ui-xs text-upsilon-magenta uppercase tracking-widest animate-pulse">
            SELECTED
        </div>

        <!-- Rolling indicator bar at bottom -->
        <div class="absolute bottom-0 left-0 right-0 h-0.5"
            :class="state === 'spinning'
                ? 'bg-upsilon-cyan animate-scan'
                : state === 'done' && isWinner
                    ? 'bg-upsilon-magenta'
                    : 'bg-upsilon-steel/30'">
        </div>
    </div>
</template>

<style scoped>
.scanlines {
    background: repeating-linear-gradient(
        0deg,
        transparent,
        transparent 2px,
        rgba(0, 0, 0, 0.4) 2px,
        rgba(0, 0, 0, 0.4) 4px
    );
}
.shadow-glow-winner {
    box-shadow: 0 0 12px rgba(255, 0, 255, 0.5), 0 0 24px rgba(255, 0, 255, 0.2);
}
.neon-text {
    text-shadow: 0 0 6px rgba(255, 0, 255, 0.8), 0 0 12px rgba(255, 0, 255, 0.4);
}
@keyframes scan {
    0%   { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
.animate-scan {
    animation: scan 0.6s linear infinite;
}
</style>
