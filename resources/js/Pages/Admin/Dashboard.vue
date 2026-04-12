<script setup>
/** @spec-link [[ui_admin_dashboard]] */
import { Head, Link } from '@inertiajs/vue3';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';

const props = defineProps({
    user: Object
});
</script>

<template>
    <Head title="Admin Command" />

    <TacticalLayout :user="user">
        <div class="relative z-10 flex-1 flex flex-col items-center justify-center p-6 max-w-[1200px] mx-auto w-full">
            <h1 class="text-4xl font-scifi text-upsilon-magenta uppercase tracking-tighter mb-2 shadow-glow-magenta/20">
                Administrative Terminal
            </h1>
            <p class="text-upsilon-magenta/60 font-mono text-xs uppercase tracking-widest mb-12">
                System Integrity & Maintenance Hub
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-[800px]">
                <!-- User Management Tile -->
                <Link 
                    href="/admin/users"
                    class="group relative p-8 bg-black/40 border border-upsilon-magenta/30 hover:border-upsilon-magenta hover:bg-upsilon-magenta/5 transition-all duration-500 overflow-hidden"
                >
                    <div class="absolute top-0 right-0 p-2 text-[8px] font-mono text-upsilon-magenta/40 uppercase">UC-08</div>
                    <div class="relative z-10 flex flex-col items-center text-center space-y-4">
                        <div class="w-16 h-16 rounded-full border border-upsilon-magenta/40 flex items-center justify-center group-hover:scale-110 group-hover:bg-upsilon-magenta/10 transition-all duration-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-upsilon-magenta" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-scifi text-white uppercase tracking-tight">User Management</h2>
                            <p class="text-[10px] font-mono text-upsilon-magenta/60 uppercase tracking-widest mt-1">Audit, Anonymize, Terminate</p>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-0 bg-upsilon-magenta group-hover:w-full transition-all duration-700"></div>
                </Link>

                <!-- History Audit Tile (Placeholder for now) -->
                <div 
                    class="group grayscale opacity-50 relative p-8 bg-black/40 border border-upsilon-steel/30 cursor-not-allowed"
                >
                    <div class="absolute top-0 right-0 p-2 text-[8px] font-mono text-upsilon-steel/40 uppercase">UC-09</div>
                    <div class="relative z-10 flex flex-col items-center text-center space-y-4">
                        <div class="w-16 h-16 rounded-full border border-upsilon-steel/40 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-upsilon-steel" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-scifi text-upsilon-steel uppercase tracking-tight">History Audit</h2>
                            <p class="text-[10px] font-mono text-upsilon-steel/60 uppercase tracking-widest mt-1">Match Logs & Maintenance</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-12 text-[8px] font-mono text-upsilon-magenta/30 uppercase tracking-[0.3em] animate-pulse">
                Authorized Personnel Only :: System Trace Active
            </div>
        </div>
    </TacticalLayout>
</template>
