<!-- @spec-link [[ui_modal_box]] -->
<script setup>
import Modal from '@/Components/Modal.vue';

defineProps({
    show: Boolean,
    maxWidth: {
        type: String,
        default: '2xl',
    },
    title: String,
    subtitle: String,
    closeable: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['close']);
</script>

<template>
    <Modal :show="show" :max-width="maxWidth" :closeable="closeable" content-class="bg-transparent" @close="$emit('close')">
        <div class="relative bg-upsilon-gunmetal/90 backdrop-blur-xl border border-upsilon-magenta/30 shadow-glow-magenta/20 p-8">
            <!-- Corner Accents -->
            <div class="absolute -top-px -left-px w-6 h-6 border-t-2 border-l-2 border-upsilon-magenta"></div>
            <div class="absolute -bottom-px -right-px w-6 h-6 border-b-2 border-r-2 border-upsilon-magenta"></div>
            
            <!-- Header -->
            <div class="mb-8 flex justify-between items-end border-b border-upsilon-magenta/20 pb-4">
                <div>
                    <h2 class="text-2xl font-scifi font-bold text-white uppercase tracking-tighter">
                        {{ title }}
                    </h2>
                    <div v-if="subtitle" class="mt-1 text-upsilon-lime font-mono text-[10px] uppercase tracking-[.2em]">{{ subtitle }}</div>
                </div>
                <button v-if="closeable" @click="$emit('close')" class="text-upsilon-lime hover:text-upsilon-magenta transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="relative z-10">
                <slot />
            </div>
        </div>
    </Modal>
</template>

<style scoped>
.shadow-glow-magenta {
    filter: drop-shadow(0 0 5px rgba(255, 0, 255, 0.5));
}
</style>
