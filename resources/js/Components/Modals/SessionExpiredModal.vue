<!-- @spec-link [[req_ui_session_timeout]] -->
<script setup>
import { router } from '@inertiajs/vue3';
import ModalBox from '@/Components/ModalBox.vue';
import { isSessionExpired } from '@/services/auth';

const reauthenticate = () => {
    isSessionExpired.value = false;
    router.visit('/');
};
</script>

<template>
    <ModalBox 
        :show="isSessionExpired" 
        title="LINK TERMINATED" 
        subtitle="Neural Synchronization Failure"
        :closeable="false"
    >
        <div class="space-y-6">
            <div class="p-4 bg-upsilon-magenta/5 border-l-2 border-upsilon-magenta font-mono text-sm text-upsilon-lime leading-relaxed">
                <p class="mb-2">/// ALERT: Session encryption keys have been revoked by the central Uplink.</p>
                <p class="mb-2">/// STATUS: Neural path safety protocols engaged. Current session state is read-only and deprecated.</p>
                <p>/// ACTION: Re-authentication is mandatory to re-establish secure synchronization with the tactical network.</p>
            </div>

            <div class="pt-4 border-t border-upsilon-magenta/20">
                <button 
                    @click="reauthenticate"
                    class="w-full py-4 bg-upsilon-magenta hover:bg-upsilon-magenta/80 text-white font-scifi font-bold text-xl uppercase tracking-[0.2em] transition-all duration-300 shadow-glow-magenta/40"
                >
                    Re-establish Synchronization
                </button>
            </div>
        </div>
    </ModalBox>
</template>

<style scoped>
.shadow-glow-magenta {
    box-shadow: 0 0 20px rgba(255, 0, 255, 0.3);
}
</style>
