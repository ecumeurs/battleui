<!-- 
@spec-link [[ui_registration]]
@spec-link [[ui_registration_minimal_form_fields]]
-->
<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { register } from '@/services/auth';

const form = ref({
    account_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    full_address: '',
    birth_date: '',
});

const errors = ref({});
const processing = ref(false);

const submit = async () => {
    processing.value = true;
    errors.value = {};
    
    try {
        await register(form.value);
        router.visit('/dashboard');
    } catch (err) {
        if (err.meta && err.meta.errors) {
            errors.value = err.meta.errors;
        } else {
            errors.value = { global: err.message || 'Registration failed' };
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <Head title="Establish Link" />

    <div class="relative min-h-screen bg-upsilon-void overflow-hidden flex items-center justify-center font-sans selection:bg-upsilon-cyan selection:text-upsilon-void py-12">
        <!-- Background Layer -->
        <div class="absolute inset-0 bg-hero-bg bg-cover bg-center opacity-30"></div>
        <div class="absolute inset-0 bg-panel-texture bg-repeat opacity-20"></div>

        <!-- Layout decorations -->
        <div class="absolute top-4 left-4 font-mono text-upsilon-cyan text-[10px] tracking-widest uppercase">
            [ AUTH_PROTOCOL_REGISTRATION ]
        </div>

        <div class="relative z-10 w-full max-w-2xl px-8 py-10 bg-upsilon-gunmetal/40 backdrop-blur-md border border-upsilon-magenta/30 shadow-glow-magenta/20">
            <div class="mb-8 flex justify-between items-end border-b border-upsilon-magenta/30 pb-4">
                <div>
                    <h1 class="text-3xl font-scifi font-bold text-white uppercase tracking-tighter">
                        Establish <span class="text-upsilon-magenta shadow-glow-magenta">Link</span>
                    </h1>
                    <div class="mt-1 text-upsilon-lime font-mono text-[10px] uppercase tracking-[.2em]">NEW_ENTITY_CREATION</div>
                </div>
                <div class="font-mono text-upsilon-cyan text-[10px] text-right">
                    SECURE_CH: v1.02<br />
                    SIGNAL: STABLE
                </div>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                <!-- Left Column -->
                <div class="space-y-6">
                    <div>
                        <label class="block font-mono text-upsilon-cyan text-xs uppercase mb-2">Account Name</label>
                        <input 
                            v-model="form.account_name"
                            type="text" 
                            class="w-full bg-black/50 border border-upsilon-steel text-white px-4 py-2 font-mono text-sm focus:outline-none focus:border-upsilon-cyan focus:ring-1 focus:ring-upsilon-cyan"
                            placeholder="UNIQUE_ID"
                        />
                        <p v-if="errors.account_name" class="mt-1 text-upsilon-magenta text-[10px] font-mono uppercase">{{ errors.account_name[0] }}</p>
                    </div>

                    <div>
                        <label class="block font-mono text-upsilon-cyan text-xs uppercase mb-2">Email</label>
                        <input 
                            v-model="form.email"
                            type="email" 
                            class="w-full bg-black/50 border border-upsilon-steel text-white px-4 py-2 font-mono text-sm focus:outline-none focus:border-upsilon-cyan focus:ring-1 focus:ring-upsilon-cyan"
                            placeholder="COMM_LINK"
                        />
                        <p v-if="errors.email" class="mt-1 text-upsilon-magenta text-[10px] font-mono uppercase">{{ errors.email[0] }}</p>
                    </div>

                    <div>
                        <label class="block font-mono text-upsilon-cyan text-xs uppercase mb-2">Password (Min 15 chars)</label>
                        <input 
                            v-model="form.password"
                            type="password" 
                            class="w-full bg-black/50 border border-upsilon-steel text-white px-4 py-2 font-mono text-sm focus:outline-none focus:border-upsilon-cyan focus:ring-1 focus:ring-upsilon-cyan"
                        />
                        <p v-if="errors.password" class="mt-1 text-upsilon-magenta text-[10px] font-mono uppercase">{{ errors.password[0] }}</p>
                    </div>

                    <div>
                        <label class="block font-mono text-upsilon-cyan text-xs uppercase mb-2">Confirm Password</label>
                        <input 
                            v-model="form.password_confirmation"
                            type="password" 
                            class="w-full bg-black/50 border border-upsilon-steel text-white px-4 py-2 font-mono text-sm focus:outline-none focus:border-upsilon-cyan focus:ring-1 focus:ring-upsilon-cyan"
                        />
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <div>
                        <label class="block font-mono text-upsilon-cyan text-xs uppercase mb-2">Full Residential Address</label>
                        <textarea 
                            v-model="form.full_address"
                            rows="4"
                            class="w-full bg-black/50 border border-upsilon-steel text-white px-4 py-2 font-mono text-sm focus:outline-none focus:border-upsilon-cyan focus:ring-1 focus:ring-upsilon-cyan resize-none"
                            placeholder="GRID_COORDINATES"
                        ></textarea>
                        <p v-if="errors.full_address" class="mt-1 text-upsilon-magenta text-[10px] font-mono uppercase">{{ errors.full_address[0] }}</p>
                    </div>

                    <div>
                        <label class="block font-mono text-upsilon-cyan text-xs uppercase mb-2">Birth Date</label>
                        <input 
                            v-model="form.birth_date"
                            type="date" 
                            class="w-full bg-black/50 border border-upsilon-steel text-white px-4 py-2 font-mono text-sm focus:outline-none focus:border-upsilon-cyan focus:ring-1 focus:ring-upsilon-cyan"
                        />
                        <p v-if="errors.birth_date" class="mt-1 text-upsilon-magenta text-[10px] font-mono uppercase">{{ errors.birth_date[0] }}</p>
                    </div>
                </div>

                <!-- Footer spanning columns -->
                <div class="md:col-span-2 mt-4 space-y-6">
                    <div v-if="errors.global" class="p-3 border border-upsilon-magenta bg-upsilon-magenta/10 text-upsilon-magenta text-xs font-mono uppercase text-center">
                        {{ errors.global }}
                    </div>

                    <button 
                        type="submit"
                        :disabled="processing"
                        class="w-full relative py-4 bg-upsilon-cyan text-upsilon-void font-scifi font-bold text-xl uppercase tracking-widest transition-all duration-300 hover:scale-[1.02] hover:shadow-neon disabled:opacity-50 disabled:scale-100"
                    >
                        <span class="relative z-10">{{ processing ? 'PROCESSING_REQUEST...' : 'Initialize Entity' }}</span>
                    </button>

                    <div class="text-center">
                        <p class="text-upsilon-lime text-xs font-mono uppercase">
                            Already established? 
                            <Link href="/login" class="text-upsilon-magenta hover:underline">Re-Authenticate</Link>
                        </p>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
