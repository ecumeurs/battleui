<script setup>
const props = defineProps({
    lastUpdate: {
        type: String,
        default: '0s ago'
    }
});
</script>

<template>
    <footer class="relative z-10 w-full px-8 py-2 bg-black/80 border-t border-upsilon-steel/20 flex justify-between items-center font-mono text-[8px] text-upsilon-cyan uppercase tracking-widest">
        <div class="flex items-center gap-4">
            <span class="flex items-center gap-2">
                <div class="w-1.5 h-1.5 rounded-full bg-upsilon-lime animate-pulse"></div>
                Global_Stats_Updated: {{ lastUpdate }}
            </span>
            <span class="text-upsilon-steel">|</span>
            <span>Connection: Stable [124.66.22.11]</span>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-upsilon-steel">Sector_Status: Minimal Risk</span>
            <span class="text-upsilon-cyan">Upsilon_OS_v2.4</span>
        </div>
    </footer>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
