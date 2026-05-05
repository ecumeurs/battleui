<!-- Toast-style confirmation bar for the roulette two-step flow.
     Appears at top-center; user must confirm before SkillRouletteModal opens.
     @spec-link [[req_ui_look_and_feel]] -->
<script setup>
defineProps({
    show:          { type: Boolean, default: false },
    characterName: { type: String,  default: '' },
});

defineEmits(['confirm', 'dismiss']);
</script>

<template>
    <Teleport to="body">
        <Transition name="roulette-notify">
            <div
                v-if="show"
                class="fixed top-4 left-1/2 z-50
                       bg-upsilon-gunmetal/90 backdrop-blur-xl
                       border border-upsilon-magenta/40
                       border-t-2 border-t-upsilon-magenta
                       px-6 py-3 flex items-center gap-5 relative"
                style="transform: translateX(-50%);"
            >
                <!-- Corner accents -->
                <div class="absolute top-0 left-0 w-3 h-3 border-t-2 border-l-2 border-upsilon-magenta pointer-events-none"></div>
                <div class="absolute top-0 right-0 w-3 h-3 border-t-2 border-r-2 border-upsilon-magenta pointer-events-none"></div>

                <span class="font-mono text-[9px] uppercase tracking-widest text-upsilon-cyan whitespace-nowrap">
                    SKILL SLOT AVAILABLE — {{ characterName }}
                </span>

                <button
                    @click="$emit('confirm')"
                    class="font-scifi text-[9px] uppercase tracking-widest text-upsilon-magenta hover:text-white border border-upsilon-magenta/40 hover:border-upsilon-magenta px-3 py-1 whitespace-nowrap"
                    style="transition: color 150ms linear, border-color 150ms linear;"
                >
                    SCAVENGE A SKILL
                </button>

                <button
                    @click="$emit('dismiss')"
                    class="font-mono text-[9px] uppercase tracking-widest text-upsilon-cyan/50 hover:text-upsilon-cyan"
                    style="transition: color 150ms linear;"
                >
                    ABORT
                </button>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.roulette-notify-enter-active,
.roulette-notify-leave-active {
    transition: opacity 150ms linear, transform 150ms linear;
}
.roulette-notify-enter-from,
.roulette-notify-leave-to {
    opacity: 0;
    transform: translateX(-50%) translateY(-10px);
}
.roulette-notify-enter-to,
.roulette-notify-leave-from {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}
</style>
