<!-- @spec-link [[ui_character_full_stat_panel]] -->
<script setup>
import StatRow from './StatRow.vue';

defineProps({
    stats: {
        type: Array,
        required: true
    },
    canUpgrade: {
        type: Boolean,
        default: false
    }
});

defineEmits(['upgrade']);
</script>

<template>
    <div class="space-y-0.5">
        <StatRow 
            v-for="stat in stats" 
            :key="stat.key" 
            :stat="stat" 
            :can-upgrade="canUpgrade"
            @upgrade="$emit('upgrade', $event)"
        />
    </div>
</template>
