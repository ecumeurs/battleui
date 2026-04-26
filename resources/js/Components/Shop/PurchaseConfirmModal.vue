<!-- @spec-link [[ui_shop]] -->
<script setup>
import ConfirmModal from '@/Components/Modals/ConfirmModal.vue';

const props = defineProps({
    show: Boolean,
    item: {
        type: Object,
        default: null
    }
});

defineEmits(['close', 'confirm']);
</script>

<template>
    <ConfirmModal 
        :show="show"
        title="Transaction Validation"
        :message="item ? `Authorized acquisition of ${item.name} for ${item.cost} credits? This process is irreversible once hardwired.` : ''"
        confirm-text="Acquire"
        cancel-text="Abort"
        @close="$emit('close')"
        @confirm="$emit('confirm')"
    />
</template>
