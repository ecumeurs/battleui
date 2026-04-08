<script setup>
/** @spec-link [[requirement_customer_user_account]] */
import { ref } from 'vue';
import EditIdentityModal from '@/Components/Modals/EditIdentityModal.vue';
import ChangePasswordModal from '@/Components/Modals/ChangePasswordModal.vue';
import { router } from '@inertiajs/vue3';
import auth from '@/services/auth';

const props = defineProps({
    user: Object,
    playerStats: Object
});

const showEditModal = ref(false);
const showPasswordModal = ref(false);

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
            <h2 class="font-scifi text-[10px] text-upsilon-lime uppercase tracking-[0.2em] mb-4">Combat Performance</h2>
            
            <div class="text-center mb-4">
                <div class="text-4xl font-scifi text-white">{{ playerStats.ratio }} <span class="text-[10px] text-upsilon-lime">W/L</span></div>
                
                <div class="w-full h-1 flex mt-4 border border-upsilon-steel/20 bg-black/40">
                    <div class="bg-upsilon-lime h-full shadow-[0_0_5px_theme('colors.upsilon.lime')]" :style="{ width: (parseFloat(playerStats.ratio) / 4 * 100) + '%' }"></div>
                    <div class="bg-upsilon-magenta h-full shadow-[0_0_5px_theme('colors.upsilon.magenta')]" :style="{ width: (100 - (parseFloat(playerStats.ratio) / 4 * 100)) + '%' }"></div>
                </div>
                
                <div class="flex justify-between mt-2 font-mono text-[8px] text-upsilon-lime uppercase">
                    <span>Wins: {{ playerStats.wins }}</span>
                    <span>Losses: {{ playerStats.losses }}</span>
                </div>
            </div>
        </div>

        <!-- Identity Management -->
        <div class="p-5 bg-upsilon-gunmetal/20 border border-upsilon-steel/30 backdrop-blur-sm relative @spec-link [[customer_user_account]]">
            <div class="absolute -top-px -right-px w-2 h-2 border-t border-r border-upsilon-cyan"></div>
            <div class="absolute -bottom-px -left-px w-2 h-2 border-b border-l border-upsilon-cyan"></div>
            <h2 class="font-scifi text-[10px] text-upsilon-lime uppercase tracking-[0.2em] mb-4">Identity Management</h2>
            
            <div class="space-y-3">
                <button 
                    @click="showEditModal = true"
                    class="w-full text-left p-3 border border-upsilon-steel/20 bg-black/30 font-mono text-[9px] text-upsilon-lime uppercase hover:border-upsilon-cyan hover:text-white transition-all"
                >
                    Edit Identity Data
                </button>
                <button 
                    @click="showPasswordModal = true"
                    class="w-full text-left p-3 border border-upsilon-steel/20 bg-black/30 font-mono text-[9px] text-upsilon-lime uppercase hover:border-upsilon-cyan hover:text-white transition-all"
                >
                    Change Credentials
                </button>
                <button 
                    @click="exportData"
                    class="w-full text-left p-3 border border-upsilon-steel/20 bg-black/30 font-mono text-[9px] text-upsilon-lime uppercase hover:border-upsilon-cyan hover:text-white transition-all @spec-link [[rule_gdpr_compliance]]"
                >
                    GDPR Archive Export
                </button>
                <button 
                    @click="initiateTermination"
                    class="w-full text-left p-3 border border-upsilon-magenta/30 bg-black/30 font-mono text-[9px] text-upsilon-magenta uppercase hover:bg-upsilon-magenta/10 transition-all @spec-link [[rule_gdpr_compliance]]"
                >
                    Termination Protocol
                </button>
            </div>
        </div>

        <!-- Modals -->
        <EditIdentityModal :show="showEditModal" :user="user" @close="showEditModal = false" />
        <ChangePasswordModal :show="showPasswordModal" @close="showPasswordModal = false" />
    </aside>
</template>
