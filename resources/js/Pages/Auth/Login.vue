<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { login } from '@/services/auth';

const form = ref({
    account_name: '',
    password: '',
});

const errors = ref({});
const processing = ref(false);

const submit = async () => {
    processing.value = true;
    errors.value = {};
    
    try {
        await login(form.value);
        router.visit('/dashboard');
    } catch (err) {
        if (err.meta && err.meta.errors) {
            errors.value = err.meta.errors;
        } else {
            errors.value = { global: err.message || 'Login failed' };
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <Head title="Log in" />

    <div class="relative min-h-screen bg-upsilon-void overflow-hidden flex items-center justify-center font-sans selection:bg-upsilon-cyan selection:text-upsilon-void">
        <!-- Background Layer -->
        <div class="absolute inset-0 bg-hero-bg bg-cover bg-center opacity-30"></div>
        <div class="absolute inset-0 bg-panel-texture bg-repeat opacity-20"></div>

        <!-- Layout decorations -->
        <div class="absolute top-4 left-4 font-mono text-upsilon-cyan text-[10px] tracking-widest uppercase">
            [ AUTH_PROTOCOL_LOGIN ]
        </div>

        <div class="relative z-10 w-full max-w-md px-6 py-12 bg-upsilon-gunmetal/40 backdrop-blur-md border-t-2 border-b-2 border-upsilon-magenta/50 shadow-glow-magenta">
            <div class="mb-10 text-center">
                <Link href="/" class="text-4xl font-scifi font-bold text-white uppercase tracking-tighter">
                    UPSILON<span class="text-upsilon-cyan italic">BATTLE</span>
                </Link>
                <div class="mt-2 text-upsilon-steel font-mono text-xs uppercase tracking-[.2em]">IDENTIFICATION_REQUIRED</div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label class="block font-mono text-upsilon-cyan text-xs uppercase mb-2 tracking-widest">Account Name</label>
                    <input 
                        v-model="form.account_name"
                        type="text" 
                        class="w-full bg-black/50 border border-upsilon-steel text-white px-4 py-3 font-mono focus:outline-none focus:border-upsilon-cyan focus:ring-1 focus:ring-upsilon-cyan transition-colors"
                        placeholder="SURVIVOR_ID"
                    />
                    <p v-if="errors.account_name" class="mt-1 text-upsilon-magenta text-xs font-mono uppercase">{{ errors.account_name[0] }}</p>
                </div>

                <div>
                    <label class="block font-mono text-upsilon-cyan text-xs uppercase mb-2 tracking-widest">Password</label>
                    <input 
                        v-model="form.password"
                        type="password" 
                        class="w-full bg-black/50 border border-upsilon-steel text-white px-4 py-3 font-mono focus:outline-none focus:border-upsilon-cyan focus:ring-1 focus:ring-upsilon-cyan transition-colors"
                        placeholder="********"
                    />
                    <p v-if="errors.password" class="mt-1 text-upsilon-magenta text-xs font-mono uppercase">{{ errors.password[0] }}</p>
                </div>

                <div v-if="errors.global" class="p-3 border border-upsilon-magenta bg-upsilon-magenta/10 text-upsilon-magenta text-xs font-mono uppercase text-center">
                    {{ errors.global }}
                </div>

                <button 
                    type="submit"
                    :disabled="processing"
                    class="w-full relative py-4 bg-upsilon-cyan text-upsilon-void font-scifi font-bold text-xl uppercase tracking-widest transition-all duration-300 hover:scale-[1.02] hover:shadow-neon disabled:opacity-50 disabled:scale-100"
                >
                    <span class="relative z-10">{{ processing ? 'Verifying...' : 'Authenticate' }}</span>
                </button>

                <div class="text-center mt-8">
                    <p class="text-upsilon-steel text-xs font-mono uppercase">
                        New survivor? 
                        <Link href="/register" class="text-upsilon-magenta hover:underline">Establish Link</Link>
                    </p>
                </div>
            </form>
        </div>

        <!-- Corner Accents -->
        <div class="absolute top-8 left-8 w-2 h-32 bg-upsilon-cyan/20"></div>
        <div class="absolute top-8 left-8 w-32 h-2 bg-upsilon-cyan/20"></div>
        <div class="absolute bottom-8 right-8 w-2 h-32 bg-upsilon-magenta/20"></div>
        <div class="absolute bottom-8 right-8 w-32 h-2 bg-upsilon-magenta/20"></div>
    </div>
</template>
