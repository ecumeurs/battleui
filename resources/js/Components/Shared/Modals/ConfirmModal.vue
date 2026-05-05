<script setup>
import { ref } from 'vue';
import ModalBox from '@/Components/Shared/ModalBox.vue';

const props = defineProps({
    show: Boolean,
    title: { type: String, default: 'Protocol Confirmation' },
    message: { type: String, default: 'Are you sure you want to proceed with this action?' },
    confirmText: { type: String, default: 'Confirm' },
    cancelText: { type: String, default: 'Abort' },
    type: { type: String, default: 'info' }, // 'info', 'danger', 'warning'
});

const emit = defineEmits(['close', 'confirm']);

const handleConfirm = () => {
    emit('confirm');
    emit('close');
};

const handleCancel = () => {
    emit('close');
};

const getTitleColor = () => {
    if (props.type === 'danger') return 'text-upsilon-magenta';
    if (props.type === 'warning') return 'text-upsilon-orange';
    return 'text-upsilon-cyan';
};

const getBtnClass = () => {
    if (props.type === 'danger') return 'bg-upsilon-magenta/80 hover:bg-upsilon-magenta shadow-glow-magenta/40';
    if (props.type === 'warning') return 'bg-upsilon-orange/80 hover:bg-upsilon-orange shadow-glow-orange/40';
    return 'bg-upsilon-cyan/80 hover:bg-upsilon-cyan shadow-glow-cyan/40';
};
</script>

<template>
    <ModalBox 
        :show="show" 
        :title="title" 
        subtitle="System Validation Layer"
        @close="handleCancel"
    >
        <div class="space-y-8">
            <div class="flex items-start gap-4">
                <div class="mt-1 flex-shrink-0 animate-pulse">
                    <svg v-if="type === 'danger'" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-upsilon-magenta" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-upsilon-cyan" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-white font-mono text-ui-md leading-relaxed tracking-wide opacity-90">
                    {{ message }}
                </p>
            </div>

            <div class="flex gap-4 pt-4 border-t border-upsilon-steel/10">
                <button 
                    @click="handleCancel"
                    class="flex-1 py-3 border border-upsilon-steel/30 text-upsilon-steel hover:text-white hover:border-white font-mono text-ui-sm uppercase tracking-widest transition-all duration-300"
                >
                    {{ cancelText }}
                </button>
                <button 
                    @click="handleConfirm"
                    :class="['flex-1 py-3 text-white font-scifi font-bold text-lg uppercase tracking-widest transition-all duration-300', getBtnClass()]"
                >
                    {{ confirmText }}
                </button>
            </div>
        </div>
    </ModalBox>
</template>

<style scoped>
.shadow-glow-magenta\/40 { box-shadow: 0 0 20px rgba(255, 0, 255, 0.2); }
.shadow-glow-cyan\/40 { box-shadow: 0 0 20px rgba(0, 242, 255, 0.2); }
.shadow-glow-orange\/40 { box-shadow: 0 0 20px rgba(255, 140, 0, 0.2); }
</style>
