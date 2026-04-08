<script setup>
import { ref } from 'vue';
import ModalBox from '@/Components/ModalBox.vue';
import auth from '@/services/auth';

const props = defineProps({
    show: Boolean
});

const emit = defineEmits(['close']);

const form = ref({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const errors = ref({});
const processing = ref(false);

const submit = async () => {
    processing.value = true;
    errors.value = {};
    
    try {
        await auth.post('/auth/password', form.value);
        emit('close');
        alert('Authentication credentials rotated successfully.');
        form.value = { current_password: '', password: '', password_confirmation: '' };
    } catch (err) {
        if (err.meta && err.meta.errors) {
            errors.value = err.meta.errors;
        } else {
            errors.value = { global: err.message || 'Access denied. Credentials mismatch.' };
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <ModalBox 
        :show="show" 
        title="Rotate Credentials" 
        subtitle="Updating secure access patterns"
        @close="emit('close')"
    >
        <form @submit.prevent="submit" class="space-y-6">
            <div class="space-y-4">
                <div>
                    <label class="block font-mono text-upsilon-cyan text-[10px] uppercase mb-1">Current Password</label>
                    <input 
                        v-model="form.current_password"
                        type="password" 
                        class="w-full bg-black/40 border border-upsilon-steel/40 text-white px-3 py-2 font-mono text-sm focus:outline-none focus:border-upsilon-cyan"
                    />
                    <p v-if="errors.current_password" class="mt-1 text-upsilon-magenta text-[10px] font-mono uppercase">{{ errors.current_password[0] }}</p>
                </div>

                <div class="border-t border-upsilon-steel/10 pt-4">
                    <label class="block font-mono text-upsilon-cyan text-[10px] uppercase mb-1">New Password (Min 15 chars)</label>
                    <input 
                        v-model="form.password"
                        type="password" 
                        class="w-full bg-black/40 border border-upsilon-steel/40 text-white px-3 py-2 font-mono text-sm focus:outline-none focus:border-upsilon-cyan"
                    />
                    <p v-if="errors.password" class="mt-1 text-upsilon-magenta text-[10px] font-mono uppercase">{{ errors.password[0] }}</p>
                </div>

                <div>
                    <label class="block font-mono text-upsilon-cyan text-[10px] uppercase mb-1">Confirm New Password</label>
                    <input 
                        v-model="form.password_confirmation"
                        type="password" 
                        class="w-full bg-black/40 border border-upsilon-steel/40 text-white px-3 py-2 font-mono text-sm focus:outline-none focus:border-upsilon-cyan"
                    />
                </div>
            </div>

            <div v-if="errors.global" class="p-3 border border-upsilon-magenta bg-upsilon-magenta/10 text-upsilon-magenta text-[10px] font-mono uppercase text-center">
                {{ errors.global }}
            </div>

            <div class="border-t border-upsilon-magenta/10 pt-6">
                <button 
                    type="submit"
                    :disabled="processing"
                    class="w-full py-3 bg-upsilon-magenta/80 hover:bg-upsilon-magenta text-white font-scifi font-bold text-lg uppercase tracking-widest transition-all duration-300 disabled:opacity-50"
                >
                    {{ processing ? 'ROTATING...' : 'Rotate Credentials' }}
                </button>
            </div>
        </form>
    </ModalBox>
</template>
