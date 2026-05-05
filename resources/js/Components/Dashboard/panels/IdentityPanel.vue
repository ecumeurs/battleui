<!-- Identity management panel: identity data tab + credentials tab.
     @spec-link [[requirement_customer_user_account]] -->
<script setup>
import { ref, watch } from 'vue';
import { useDashboardState } from '@/Composables/useDashboardState';
import auth from '@/services/auth';

const props = defineProps({
    user: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const { updateUser } = useDashboardState();

const activeTab  = ref('identity');  // 'identity' | 'credentials'

// Identity form
const identityForm = ref({
    account_name: '',
    email: '',
    full_address: '',
    birth_date: '',
});
const identityErrors  = ref({});
const identityLoading = ref(false);
const identitySuccess = ref(false);

// Password form
const passwordForm = ref({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const passwordErrors  = ref({});
const passwordLoading = ref(false);
const passwordSuccess = ref(false);

watch(() => props.user, (u) => {
    if (u) {
        identityForm.value = {
            account_name: u.account_name || '',
            email: u.email || '',
            full_address: u.full_address || '',
            birth_date: u.birth_date ? u.birth_date.split('T')[0] : '',
        };
    }
}, { immediate: true });

async function submitIdentity() {
    identityLoading.value = true;
    identityErrors.value = {};
    identitySuccess.value = false;
    try {
        const data = await auth.post('/auth/update', identityForm.value);
        updateUser(data ?? identityForm.value);
        identitySuccess.value = true;
    } catch (err) {
        if (err.meta?.errors) {
            identityErrors.value = err.meta.errors;
        } else {
            identityErrors.value = { global: err.message || 'Uplink failed.' };
        }
    } finally {
        identityLoading.value = false;
    }
}

async function submitPassword() {
    passwordLoading.value = true;
    passwordErrors.value = {};
    passwordSuccess.value = false;
    try {
        await auth.post('/auth/password', passwordForm.value);
        passwordSuccess.value = true;
        passwordForm.value = { current_password: '', password: '', password_confirmation: '' };
    } catch (err) {
        if (err.meta?.errors) {
            passwordErrors.value = err.meta.errors;
        } else {
            passwordErrors.value = { global: err.message || 'Access denied. Credentials mismatch.' };
        }
    } finally {
        passwordLoading.value = false;
    }
}
</script>

<template>
    <div class="flex flex-col flex-1 overflow-hidden">
        <!-- Corner accent -->
        <div class="absolute top-0 left-0 w-4 h-4 border-t-2 border-l-2 border-upsilon-cyan/60 pointer-events-none"></div>

        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-upsilon-cyan/20 shrink-0">
            <span class="font-scifi text-ui-sm uppercase tracking-[0.3em] text-upsilon-cyan">
                ◈ Identity Management
            </span>
            <button
                @click="$emit('close')"
                class="font-mono text-ui-sm text-upsilon-magenta hover:text-white border border-upsilon-magenta/30 hover:border-upsilon-magenta px-2 py-0.5"
                style="transition: color 150ms linear, border-color 150ms linear;"
            >
                SEVER ✕
            </button>
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-upsilon-cyan/10 shrink-0">
            <button
                @click="activeTab = 'identity'"
                class="px-5 py-2.5 font-mono text-ui-xs uppercase tracking-widest"
                :class="activeTab === 'identity'
                    ? 'text-upsilon-cyan border-b-2 border-upsilon-cyan'
                    : 'text-upsilon-steel/60 hover:text-upsilon-steel'"
            >
                Identity Data
            </button>
            <button
                @click="activeTab = 'credentials'"
                class="px-5 py-2.5 font-mono text-ui-xs uppercase tracking-widest"
                :class="activeTab === 'credentials'
                    ? 'text-upsilon-cyan border-b-2 border-upsilon-cyan'
                    : 'text-upsilon-steel/60 hover:text-upsilon-steel'"
            >
                Credentials
            </button>
        </div>

        <!-- Tab content -->
        <div class="flex-1 overflow-y-auto p-6">

            <!-- Identity form -->
            <form v-if="activeTab === 'identity'" @submit.prevent="submitIdentity" class="space-y-6 max-w-xl">
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block font-mono text-upsilon-cyan text-ui-sm uppercase mb-1">Account Name</label>
                            <input
                                v-model="identityForm.account_name"
                                type="text"
                                class="w-full bg-black/40 border border-upsilon-steel/40 text-white px-3 py-2 font-mono text-ui-md focus:outline-none focus:border-upsilon-cyan"
                            />
                            <p v-if="identityErrors.account_name" class="mt-1 text-upsilon-magenta text-ui-xs font-mono uppercase">
                                {{ identityErrors.account_name[0] }}
                            </p>
                        </div>
                        <div>
                            <label class="block font-mono text-upsilon-cyan text-ui-sm uppercase mb-1">Email</label>
                            <input
                                v-model="identityForm.email"
                                type="email"
                                class="w-full bg-black/40 border border-upsilon-steel/40 text-white px-3 py-2 font-mono text-ui-md focus:outline-none focus:border-upsilon-cyan"
                            />
                            <p v-if="identityErrors.email" class="mt-1 text-upsilon-magenta text-ui-xs font-mono uppercase">
                                {{ identityErrors.email[0] }}
                            </p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block font-mono text-upsilon-cyan text-ui-sm uppercase mb-1">Birth Date</label>
                            <input
                                v-model="identityForm.birth_date"
                                type="date"
                                class="w-full bg-black/40 border border-upsilon-steel/40 text-white px-3 py-2 font-mono text-ui-md focus:outline-none focus:border-upsilon-cyan"
                                style="color-scheme: dark;"
                            />
                            <p v-if="identityErrors.birth_date" class="mt-1 text-upsilon-magenta text-ui-xs font-mono uppercase">
                                {{ identityErrors.birth_date[0] }}
                            </p>
                        </div>
                        <div>
                            <label class="block font-mono text-upsilon-cyan text-ui-sm uppercase mb-1">Residential Address</label>
                            <textarea
                                v-model="identityForm.full_address"
                                rows="2"
                                class="w-full bg-black/40 border border-upsilon-steel/40 text-white px-3 py-2 font-mono text-ui-md focus:outline-none focus:border-upsilon-cyan resize-none"
                            ></textarea>
                            <p v-if="identityErrors.full_address" class="mt-1 text-upsilon-magenta text-ui-xs font-mono uppercase">
                                {{ identityErrors.full_address[0] }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="identityErrors.global" class="p-3 border border-upsilon-magenta bg-upsilon-magenta/10 text-upsilon-magenta text-ui-sm font-mono uppercase">
                    {{ identityErrors.global }}
                </div>
                <div v-if="identitySuccess" class="p-3 border border-upsilon-lime/30 bg-upsilon-lime/5 text-upsilon-lime text-ui-sm font-mono uppercase">
                    ✓ Identity data synchronized.
                </div>

                <div class="border-t border-upsilon-magenta/10 pt-4">
                    <button
                        type="submit"
                        :disabled="identityLoading"
                        class="w-full py-3 bg-upsilon-cyan/80 hover:bg-upsilon-cyan text-upsilon-void font-scifi font-bold text-lg uppercase tracking-widest disabled:opacity-50"
                        style="transition: all 300ms;"
                    >
                        {{ identityLoading ? 'SYNCING...' : 'Update Records' }}
                    </button>
                </div>
            </form>

            <!-- Credentials form -->
            <form v-else-if="activeTab === 'credentials'" @submit.prevent="submitPassword" class="space-y-6 max-w-xl">
                <div class="space-y-4">
                    <div>
                        <label class="block font-mono text-upsilon-cyan text-ui-sm uppercase mb-1">Current Password</label>
                        <input
                            v-model="passwordForm.current_password"
                            type="password"
                            class="w-full bg-black/40 border border-upsilon-steel/40 text-white px-3 py-2 font-mono text-ui-md focus:outline-none focus:border-upsilon-cyan"
                        />
                        <p v-if="passwordErrors.current_password" class="mt-1 text-upsilon-magenta text-ui-sm font-mono uppercase">
                            {{ passwordErrors.current_password[0] }}
                        </p>
                    </div>
                    <div class="border-t border-upsilon-steel/10 pt-4">
                        <label class="block font-mono text-upsilon-cyan text-ui-sm uppercase mb-1">New Password (Min 15 chars)</label>
                        <input
                            v-model="passwordForm.password"
                            type="password"
                            class="w-full bg-black/40 border border-upsilon-steel/40 text-white px-3 py-2 font-mono text-ui-md focus:outline-none focus:border-upsilon-cyan"
                        />
                        <p v-if="passwordErrors.password" class="mt-1 text-upsilon-magenta text-ui-sm font-mono uppercase">
                            {{ passwordErrors.password[0] }}
                        </p>
                    </div>
                    <div>
                        <label class="block font-mono text-upsilon-cyan text-ui-sm uppercase mb-1">Confirm New Password</label>
                        <input
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            class="w-full bg-black/40 border border-upsilon-steel/40 text-white px-3 py-2 font-mono text-ui-md focus:outline-none focus:border-upsilon-cyan"
                        />
                    </div>
                </div>

                <div v-if="passwordErrors.global" class="p-3 border border-upsilon-magenta bg-upsilon-magenta/10 text-upsilon-magenta text-ui-sm font-mono uppercase">
                    {{ passwordErrors.global }}
                </div>
                <div v-if="passwordSuccess" class="p-3 border border-upsilon-lime/30 bg-upsilon-lime/5 text-upsilon-lime text-ui-sm font-mono uppercase">
                    ✓ Credentials rotated successfully.
                </div>

                <div class="border-t border-upsilon-magenta/10 pt-4">
                    <button
                        type="submit"
                        :disabled="passwordLoading"
                        class="w-full py-3 bg-upsilon-magenta/80 hover:bg-upsilon-magenta text-white font-scifi font-bold text-lg uppercase tracking-widest disabled:opacity-50"
                        style="transition: all 300ms;"
                    >
                        {{ passwordLoading ? 'ROTATING...' : 'Rotate Credentials' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
