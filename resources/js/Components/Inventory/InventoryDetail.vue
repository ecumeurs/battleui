<!-- Right-pane detail for a selected inventory item. Read-only; equipment actions are
     in the Character modal. @spec-link [[ui_inventory]] -->
<script setup>
defineProps({
    item: {
        type: Object,
        required: true,
    },
});

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
                 :class="typeColors[item.shop_item.slot] || 'text-upsilon-steel'">
                {{ slotLabels[item.shop_item.slot] || item.shop_item.slot }}
            </div>
            <h3 class="font-scifi text-xl font-bold text-white uppercase tracking-wider">
                {{ item.shop_item.name }}
            </h3>
            <div class="mt-1 flex gap-4">
                <span class="font-mono text-[8px] text-upsilon-steel uppercase">
                    QTY: {{ item.quantity }}
                </span>
                <span v-if="item.equipped_on" class="font-mono text-[8px] text-upsilon-lime uppercase">
                    LINKED → {{ item.equipped_on.name }}
                </span>
                <span v-else class="font-mono text-[8px] text-upsilon-steel/50 uppercase">
                    UNLINKED
                </span>
            </div>
        </div>

        <!-- Divider -->
        <div class="border-t border-upsilon-steel/20"></div>

        <!-- Properties -->
        <div>
            <div class="text-[8px] font-mono text-upsilon-steel uppercase tracking-[0.4em] mb-3">
                STAT MODIFIERS
            </div>
            <div v-if="item.shop_item.properties && Object.keys(item.shop_item.properties).length" class="space-y-2">
                <div
                    v-for="(value, key) in item.shop_item.properties"
                    :key="key"
                    class="flex items-center justify-between px-3 py-2 bg-black/40 border border-upsilon-steel/10"
                >
                    <span class="font-mono text-[9px] text-upsilon-steel uppercase tracking-widest">{{ key }}</span>
                    <span class="font-scifi text-[11px] text-upsilon-cyan font-bold">
                        {{ typeof value === 'number' && value > 0 ? '+' : '' }}{{ value }}
                    </span>
                </div>
            </div>
            <div v-else class="text-[9px] font-mono text-upsilon-steel/50 uppercase">
                No modifiers.
            </div>
        </div>

        <!-- Acquisition info -->
        <div class="border-t border-upsilon-steel/20 pt-4">
            <div class="text-[8px] font-mono text-upsilon-steel uppercase tracking-[0.4em] mb-2">
                ACQUISITION LOG
            </div>
            <div class="font-mono text-[8px] text-upsilon-steel/60 uppercase">
                {{ new Date(item.purchased_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) }}
            </div>
        </div>
    </div>
</template>
