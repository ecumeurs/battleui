<!-- Right-pane detail for a selected shop item. Shows stats, cost, balance, and purchase CTA.
     @spec-link [[ui_shop]] -->
<script setup>
defineProps({
    item: {
        type: Object,
        required: true,
    },
    userCredits: {
        type: Number,
        default: 0,
    },
});

defineEmits(['purchase']);

const slotLabels = {
    armor:   'ARMOR SLOT',
    weapon:  'WEAPON SLOT',
    utility: 'UTILITY SLOT',
};

const typeColors = {
    armor:   'text-upsilon-cyan',
    weapon:  'text-upsilon-magenta',
    utility: 'text-upsilon-lime',
};
</script>

<template>
    <div class="space-y-6">
        <!-- Item header -->
        <div>
            <div class="text-[8px] font-mono uppercase tracking-[0.4em] mb-1"
                 :class="typeColors[item.slot] || 'text-upsilon-steel'">
                {{ slotLabels[item.slot] || item.slot }}
            </div>
            <h3 class="font-scifi text-xl font-bold text-white uppercase tracking-wider">
                {{ item.name }}
            </h3>
        </div>

        <!-- Divider -->
        <div class="border-t border-upsilon-steel/20"></div>

        <!-- Properties -->
        <div>
            <div class="text-[8px] font-mono text-upsilon-steel uppercase tracking-[0.4em] mb-3">STAT MODIFIERS</div>
            <div v-if="item.properties && Object.keys(item.properties).length" class="space-y-2">
                <div
                    v-for="(value, key) in item.properties"
                    :key="key"
                    class="flex items-center justify-between px-3 py-2 bg-black/40 border border-upsilon-steel/10"
                >
                    <span class="font-mono text-[9px] text-upsilon-steel uppercase tracking-widest">{{ key }}</span>
                    <span class="font-scifi text-[11px] text-upsilon-cyan font-bold">
                        {{ typeof value === 'number' && value > 0 ? '+' : '' }}{{ value }}
                    </span>
                </div>
            </div>
            <div v-else class="text-[9px] font-mono text-upsilon-steel/50 uppercase">No modifiers.</div>
        </div>

        <!-- Divider -->
        <div class="border-t border-upsilon-steel/20"></div>

        <!-- Cost + balance -->
        <div class="space-y-3">
            <div class="flex items-baseline justify-between">
                <span class="font-mono text-[9px] text-upsilon-steel uppercase tracking-widest">Acquisition Cost</span>
                <span class="font-scifi text-2xl font-bold text-upsilon-cyan">{{ item.cost }} <span class="text-[10px]">CR</span></span>
            </div>
            <div class="flex items-baseline justify-between">
                <span class="font-mono text-[9px] text-upsilon-steel uppercase tracking-widest">Available Credits</span>
                <span class="font-scifi text-lg font-bold" :class="userCredits >= item.cost ? 'text-upsilon-lime' : 'text-upsilon-magenta'">
                    {{ userCredits }} <span class="text-[10px]">CR</span>
                </span>
            </div>
            <div v-if="userCredits < item.cost" class="text-[8px] font-mono text-upsilon-magenta uppercase tracking-widest text-right">
                ⚠ Insufficient credits — deficit: {{ item.cost - userCredits }} CR
            </div>
        </div>

        <!-- Purchase CTA -->
        <button
            @click="$emit('purchase', item)"
            :disabled="userCredits < item.cost"
            class="w-full py-3 font-scifi text-[11px] uppercase tracking-[0.3em] transition-all duration-300 disabled:opacity-40 disabled:cursor-not-allowed"
            :class="userCredits >= item.cost
                ? 'bg-upsilon-magenta/20 border border-upsilon-magenta text-upsilon-magenta hover:bg-upsilon-magenta hover:text-white shadow-glow-magenta/30'
                : 'bg-black/20 border border-upsilon-steel/30 text-upsilon-steel'"
        >
            {{ userCredits >= item.cost ? '⬢ ACQUIRE ASSET' : '⬡ INSUFFICIENT CREDITS' }}
        </button>
    </div>
</template>
