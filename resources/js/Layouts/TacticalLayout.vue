<!-- @spec-link [[ui_tactical_layout]] -->
<script setup>
import TacticalHeader from '@/Components/TacticalHeader.vue';
import TacticalFooter from '@/Components/TacticalFooter.vue';
import SessionExpiredModal from '@/Components/Modals/SessionExpiredModal.vue';

const props = defineProps({
    user: {
        type: Object,
        required: false,
        default: null
    },
    lastUpdate: {
        type: String,
        default: '0s ago'
    }
});
</script>

<template>
    <div class="relative min-h-screen bg-upsilon-void overflow-hidden flex flex-col font-sans selection:bg-upsilon-cyan selection:text-upsilon-void">
        <!-- Background Layer -->
        <div class="absolute inset-0 bg-hero-bg bg-cover bg-center opacity-20 pointer-events-none"></div>
        <div class="absolute inset-0 bg-panel-texture bg-repeat opacity-10 pointer-events-none"></div>

        <TacticalHeader :user="user" />

        <slot />

        <TacticalFooter :lastUpdate="lastUpdate" />

        <SessionExpiredModal />
    </div>
</template>
