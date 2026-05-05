<!-- @spec-link [[req_ui_session_timeout]] -->
<script setup>
import { router } from '@inertiajs/vue3';
import { isSessionExpired } from '@/services/auth';

const reauthenticate = () => {
    isSessionExpired.value = false;
    router.visit('/');
};
</script>

<template>
    <Teleport to="body">
        <Transition name="session-overlay">
            <div
                v-if="isSessionExpired"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm"
            >
                <div class="w-full max-w-lg mx-4 bg-upsilon-gunmetal/95 border border-upsilon-magenta/40 border-t-2 border-t-upsilon-magenta p-8 space-y-6">
                    <div>
                        <div class="font-scifi text-xl text-upsilon-magenta uppercase tracking-[0.3em] mb-1">LINK TERMINATED</div>
                        <div class="font-mono text-ui-xs text-upsilon-steel uppercase tracking-widest">Neural Synchronization Failure</div>
                    </div>

                    <div class="p-4 bg-upsilon-magenta/5 border-l-2 border-upsilon-magenta font-mono text-ui-md text-upsilon-lime leading-relaxed">
                        <p class="mb-2">/// ALERT: Session encryption keys have been revoked by the central Uplink.</p>
                        <p class="mb-2">/// STATUS: Neural path safety protocols engaged. Current session state is read-only and deprecated.</p>
                        <p>/// ACTION: Re-authentication is mandatory to re-establish secure synchronization with the tactical network.</p>
                    </div>

                    <div class="pt-2 border-t border-upsilon-magenta/20">
                        <button
                            @click="reauthenticate"
                            class="w-full py-4 bg-upsilon-magenta hover:bg-upsilon-magenta/80 text-white font-scifi font-bold text-xl uppercase tracking-[0.2em] disabled:opacity-50"
                            style="transition: all 300ms; box-shadow: 0 0 20px rgba(255,0,255,0.3);"
                        >
                            Re-establish Synchronization
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.session-overlay-enter-active,
.session-overlay-leave-active {
    transition: opacity 200ms linear;
}
.session-overlay-enter-from,
.session-overlay-leave-to {
    opacity: 0;
}
</style>
