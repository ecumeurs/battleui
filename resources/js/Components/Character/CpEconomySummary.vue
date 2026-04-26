<!-- @spec-link [[rule_progression]] -->
<script setup>
defineProps({
    cp: {
        type: Object,
        required: true
    }
});
</script>

<template>
    <div class="mt-4 pt-3 border-t border-upsilon-steel/20">
        <div class="flex justify-between items-baseline mb-1">
            <span class="font-scifi text-[8px] text-upsilon-magenta uppercase tracking-widest">Neural Capacity (CP)</span>
            <span class="font-mono text-[9px] text-white">{{ cp.spent }} / {{ cp.max }}</span>
        </div>
        <div class="h-1 bg-upsilon-gunmetal border border-upsilon-steel/30 relative overflow-hidden">
            <div 
                class="absolute inset-y-0 left-0 bg-upsilon-magenta transition-all duration-500 shadow-[0_0_8px_rgba(255,0,255,0.5)]"
                :style="{ width: `${cp.percent}%` }"
            ></div>
        </div>
        <div v-if="cp.spent < cp.max" class="mt-1 flex justify-end">
            <span class="text-[7px] text-upsilon-magenta font-scifi uppercase animate-pulse">Potential Detected</span>
        </div>
    </div>
</template>
