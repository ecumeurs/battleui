<!-- Generic two-column list+detail modal. #list on the left, #detail on the right.
     Wraps ModalBox; width set to 5xl by default for comfortable 2-column layout.
     @spec-link [[ui_theme]] -->
<script setup>
import ModalBox from '@/Components/Shared/ModalBox.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: null,
    },
    maxWidth: {
        type: String,
        default: '5xl',
    },
    listWidth: {
        type: String,
        default: 'w-1/3',
    },
    detailPlaceholder: {
        type: String,
        default: 'Select an item to view details.',
    },
    hasDetail: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['close']);
</script>

<template>
    <ModalBox
        :show="show"
        :title="title"
        :subtitle="subtitle"
        :max-width="maxWidth"
        @close="$emit('close')"
    >
        <div class="flex gap-6 min-h-[400px]">
            <!-- List pane -->
            <div :class="[listWidth, 'flex-shrink-0 overflow-y-auto max-h-[70vh] border-r border-upsilon-steel/20 pr-4']">
                <slot name="list" />
            </div>

            <!-- Detail pane -->
            <div class="flex-1 overflow-y-auto max-h-[70vh]">
                <div v-if="!hasDetail" class="h-full flex items-center justify-center">
                    <p class="text-upsilon-steel font-mono text-[10px] uppercase tracking-widest">
                        {{ detailPlaceholder }}
                    </p>
                </div>
                <slot v-else name="detail" />
            </div>
        </div>
    </ModalBox>
</template>
