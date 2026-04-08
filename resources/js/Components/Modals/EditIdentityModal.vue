<script setup>
import { ref, watch } from 'vue';
import ModalBox from '@/Components/ModalBox.vue';
import auth from '@/services/auth';

const props = defineProps({
    show: Boolean,
    user: Object
});

const emit = defineEmits(['close']);

const form = ref({
    account_name: '',
    email: '',
    full_address: '',
    birth_date: '',
});

const errors = ref({});
const processing = ref(false);

watch(() => props.show, (isVisible) => {
    if (isVisible && props.user) {
        form.value = {
            account_name: props.user.account_name || '',
            email: props.user.email || '',
            full_address: props.user.full_address || '',
            birth_date: props.user.birth_date ? props.user.birth_date.split('T')[0] : '',
        };
    }
});

const submit = async () => {
    processing.value = true;
    errors.value = {};
    
    try {
        const response = await auth.post('/auth/update', form.value);
        // Refresh page or update local state
        window.location.reload();
    } catch (err) {
        if (err.meta && err.meta.errors) {
            errors.value = err.meta.errors;
        } else {
            errors.value = { global: err.message || 'Uplink failed. Retrying...' };
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <ModalBox 
        :show="show" 
        title="Sync Identity" 
        subtitle="Modifying core entity parameters"
        @close="emit('close')"
    >
        <form @submit.prevent="submit" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-mono text-upsilon-cyan text-[10px] uppercase mb-1">Account Name</label>
                        <input 
                            v-model="form.account_name"
                            type="text" 
                            class="w-full bg-black/40 border border-upsilon-steel/40 text-white px-3 py-2 font-mono text-sm focus:outline-none focus:border-upsilon-cyan focus:ring-opacity-50"
                        />
                        <p v-if="errors.account_name" class="mt-1 text-upsilon-magenta text-[10px] font-mono uppercase">{{ errors.account_name[0] }}</p>
                    </div>

                    <div>
                        <label class="block font-mono text-upsilon-cyan text-[10px] uppercase mb-1">Email</label>
                        <input 
                            v-model="form.email"
                            type="email" 
                            class="w-full bg-black/40 border border-upsilon-steel/40 text-white px-3 py-2 font-mono text-sm focus:outline-none focus:border-upsilon-cyan"
                        />
                        <p v-if="errors.email" class="mt-1 text-upsilon-magenta text-[10px] font-mono uppercase">{{ errors.email[0] }}</p>
                    </div>
                </div>

                <!-- Right -->
                <div class="space-y-4">
                    <div>
                        <label class="block font-mono text-upsilon-cyan text-[10px] uppercase mb-1">Birth Date</label>
                        <input 
                            v-model="form.birth_date"
                            type="date" 
                            class="w-full bg-black/40 border border-upsilon-steel/40 text-white px-3 py-2 font-mono text-sm focus:outline-none focus:border-upsilon-cyan color-scheme-dark"
                        />
                        <p v-if="errors.birth_date" class="mt-1 text-upsilon-magenta text-[10px] font-mono uppercase">{{ errors.birth_date[0] }}</p>
                    </div>

                    <div>
                        <label class="block font-mono text-upsilon-cyan text-[10px] uppercase mb-1">Residential Address</label>
                        <textarea 
                            v-model="form.full_address"
                            rows="2"
                            class="w-full bg-black/40 border border-upsilon-steel/40 text-white px-3 py-2 font-mono text-sm focus:outline-none focus:border-upsilon-cyan resize-none"
                        ></textarea>
                        <p v-if="errors.full_address" class="mt-1 text-upsilon-magenta text-[10px] font-mono uppercase">{{ errors.full_address[0] }}</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-upsilon-magenta/10 pt-6">
                <button 
                    type="submit"
                    :disabled="processing"
                    class="w-full py-3 bg-upsilon-cyan/80 hover:bg-upsilon-cyan text-upsilon-void font-scifi font-bold text-lg uppercase tracking-widest transition-all duration-300 disabled:opacity-50"
                >
                    {{ processing ? 'SYNCING...' : 'Update Records' }}
                </button>
            </div>
        </form>
    </ModalBox>
</template>

<style scoped>
.color-scheme-dark {
    color-scheme: dark;
}
</style>
