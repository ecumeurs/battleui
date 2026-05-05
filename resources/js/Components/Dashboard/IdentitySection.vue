<script setup>
/** @spec-link [[requirement_customer_user_account]] */
import { router } from '@inertiajs/vue3';
import auth from '@/services/auth';

const props = defineProps({
    user: Object,
    playerStats: Object
});

const emit = defineEmits(['open-identity']);

const exportData = async () => {
    try {
        const response = await auth.get('/auth/export', { responseType: 'blob' });
        const url = window.URL.createObjectURL(new Blob([response]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'upsilon_identity_export.json');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (error) {
        console.error('Export failed:', error);
        alert('Data export failed. Security uplink unstable.');
    }
};

const initiateTermination = async () => {
    if (confirm('WARNING: TRIGGERING TERMINATION PROTOCOL WILL PERMANENTLY ANONYMIZE AND DISABLE THIS ENTITY. PROCEED?')) {
        try {
            await auth.delete('/auth/delete');
            router.visit('/login');
        } catch (error) {
            console.error('Termination failed:', error);
            alert('Termination protocol failed. Isolation required.');
        }
    }
};
</script>

<template>
    <aside class="space-y-6">
        <!-- Combat Performance -->
        <div class="p-5 bg-upsilon-gunmetal/20 border border-upsilon-steel/30 backdrop-blur-sm relative">
            <h2 class="font-scifi text-ui-sm text-upsilon-lime uppercase tracking-[0.2em] mb-4">Combat Performance</h2>

            <div class="text-center mb-4">
                <div class="text-4xl font-scifi text-white">{{ playerStats.ratio }} <span class="text-ui-sm text-upsilon-lime">W/L</span></div>

                <div class="w-full h-1 flex mt-4 border border-upsilon-steel/20 bg-black/40">
                    <div class="bg-upsilon-lime h-full shadow-[0_0_5px_theme('colors.upsilon.lime')]" :style="{ width: (parseFloat(playerStats.ratio) / 4 * 100) + '%' }"></div>
                    <div class="bg-upsilon-magenta h-full shadow-[0_0_5px_theme('colors.upsilon.magenta')]" :style="{ width: (100 - (parseFloat(playerStats.ratio) / 4 * 100)) + '%' }"></div>
                </div>

                <div class="flex justify-between mt-2 font-mono text-ui-xs text-upsilon-lime uppercase">
                    <span>Wins: {{ playerStats.wins }}</span>
                    <span>Losses: {{ playerStats.losses }}</span>
                </div>
            </div>
        </div>

        <!-- Credit Economy -->
        <div class="p-5 bg-upsilon-gunmetal/20 border border-upsilon-steel/30 backdrop-blur-sm relative">
            <h2 class="font-scifi text-ui-sm text-upsilon-cyan uppercase tracking-[0.2em] mb-4">Credit Economy</h2>
            <div class="flex items-baseline justify-between">
                <div class="text-3xl font-scifi text-white">{{ user.credits || 0 }}</div>
                <div class="text-ui-sm font-mono text-upsilon-cyan uppercase tracking-tighter">Available Credits</div>
            </div>
            <div class="mt-4 p-2 bg-upsilon-cyan/10 border border-upsilon-cyan/20 text-ui-xs font-mono text-upsilon-cyan uppercase text-center">
                Earned via Damage & Healing
            </div>
        </div>

        <!-- Identity Management -->
        <div class="p-5 bg-upsilon-gunmetal/20 border border-upsilon-steel/30 backdrop-blur-sm relative">
            <div class="absolute -top-px -right-px w-2 h-2 border-t border-r border-upsilon-cyan"></div>
            <div class="absolute -bottom-px -left-px w-2 h-2 border-b border-l border-upsilon-cyan"></div>
            <h2 class="font-scifi text-ui-sm text-upsilon-lime uppercase tracking-[0.2em] mb-4">Identity Management</h2>

            <div class="space-y-3">
                <button
                    @click="$emit('open-identity')"
                    class="w-full text-left p-3 border border-upsilon-steel/20 bg-black/30 font-mono text-ui-xs text-upsilon-lime uppercase hover:border-upsilon-cyan hover:text-white transition-all"
                >
                    Edit Identity Data
                </button>
                <button
                    @click="$emit('open-identity')"
                    class="w-full text-left p-3 border border-upsilon-steel/20 bg-black/30 font-mono text-ui-xs text-upsilon-lime uppercase hover:border-upsilon-cyan hover:text-white transition-all"
                >
                    Change Credentials
                </button>
                <button
                    @click="exportData"
                    class="w-full text-left p-3 border border-upsilon-steel/20 bg-black/30 font-mono text-ui-xs text-upsilon-lime uppercase hover:border-upsilon-cyan hover:text-white transition-all"
                >
                    GDPR Archive Export
                </button>
                <button
                    @click="initiateTermination"
                    class="w-full text-left p-3 border border-upsilon-magenta/30 bg-black/30 font-mono text-ui-xs text-upsilon-magenta uppercase hover:bg-upsilon-magenta/10 transition-all"
                >
                    Termination Protocol
                </button>
            </div>
        </div>
    </aside>
</template>
