<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import axios from 'axios';
import { getAuthUser } from '@/services/auth';

const user = ref(null);
const endpoints = ref([]);
const loading = ref(true);
const error = ref(null);
const searchQuery = ref('');

const filteredEndpoints = computed(() => {
    if (!searchQuery.value) return endpoints.value;
    const q = searchQuery.value.toLowerCase();
    return endpoints.value.filter(group => 
        group.name.toLowerCase().includes(q) || 
        group.endpoints.some(e => e.uri.toLowerCase().includes(q) || e.intent.toLowerCase().includes(q))
    );
});

onMounted(async () => {
    user.value = getAuthUser();
    try {
        const response = await axios.get('/api/v1/help');
        endpoints.value = response.data.data.endpoints;
    } catch (err) {
        error.value = "CONNECTION_STRIKE: Failed to synchronize with Tactical Discovery Registry.";
    } finally {
        loading.value = false;
    }
});

const getVerbClass = (verb) => {
    switch (verb.toUpperCase()) {
        case 'GET': return 'text-upsilon-cyan border-upsilon-cyan/30';
        case 'POST': return 'text-upsilon-magenta border-upsilon-magenta/30';
        case 'PUT': return 'text-upsilon-lime border-upsilon-lime/30';
        case 'DELETE': return 'text-red-500 border-red-500/30';
        default: return 'text-white border-white/30';
    }
};
</script>

<template>
    <Head title="API Documentation" />

    <TacticalLayout :user="user">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-scifi text-2xl text-white tracking-widest uppercase">
                    API_<span class="text-upsilon-cyan">DISCOVERY</span>_HUB
                </h2>
                <div class="text-xs font-mono text-upsilon-cyan/60 animate-pulse">
                    [ NODE_STATUS: SYNCED ]
                </div>
            </div>
        </template>

        <div class="py-6 px-4">
            <!-- Search Bar -->
            <div class="mb-8 relative group">
                <input 
                    v-model="searchQuery"
                    type="text" 
                    placeholder="Search tactical endpoints..."
                    class="w-full bg-upsilon-gunmetal/30 border border-upsilon-steel/50 rounded-none px-4 py-3 font-mono text-upsilon-cyan focus:border-upsilon-cyan focus:ring-0 transition-all placeholder:text-upsilon-steel"
                />
                <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none opacity-40">
                    <span class="text-xs font-mono text-white">[ SEARCH_QUERY ]</span>
                </div>
                <div class="absolute bottom-0 left-0 h-[1px] bg-upsilon-cyan w-0 group-focus-within:w-full transition-all duration-500 shadow-glow-cyan"></div>
            </div>

            <div v-if="loading" class="flex flex-col items-center justify-center py-20">
                <div class="w-16 h-16 border-4 border-upsilon-cyan/20 border-t-upsilon-cyan rounded-full animate-spin mb-4"></div>
                <div class="font-mono text-upsilon-cyan tracking-widest uppercase animate-pulse">Initializing documentation streams...</div>
            </div>

            <div v-else-if="error" class="bg-red-900/20 border border-red-500/50 p-6 text-center">
                <div class="text-red-500 font-bold mb-2 uppercase tracking-tighter text-xl">System Critical Malfunction</div>
                <div class="text-white/70 font-mono">{{ error }}</div>
            </div>

            <div v-else class="space-y-12">
                <div v-for="group in filteredEndpoints" :key="group.id" class="border-l-2 border-upsilon-steel/30 pl-6">
                    <h3 class="text-xl font-bold text-white mb-6 uppercase tracking-widest flex items-center">
                        <span class="w-2 h-2 bg-upsilon-magenta mr-3 shadow-glow-magenta"></span>
                        {{ group.name }}
                        <span class="ml-4 text-[10px] font-mono text-upsilon-steel px-2 py-0.5 border border-upsilon-steel/30 rounded lowercase">
                            {{ group.id }}
                        </span>
                    </h3>

                    <div class="grid grid-cols-1 gap-6">
                        <div v-for="(endpoint, idx) in group.endpoints" :key="idx" 
                            class="bg-upsilon-void/40 border border-upsilon-steel/20 p-4 relative group hover:border-upsilon-cyan/40 transition-colors"
                        >
                            <!-- Header -->
                            <div class="flex flex-wrap items-center gap-4 mb-4">
                                <span :class="['font-mono font-bold px-3 py-1 border text-sm', getVerbClass(endpoint.verb)]">
                                    {{ endpoint.verb }}
                                </span>
                                <code class="text-white font-mono text-lg tracking-tight bg-black/40 px-2 py-1">
                                    {{ endpoint.uri }}
                                </code>
                            </div>

                            <!-- Intent -->
                            <div class="text-upsilon-cyan/80 font-medium mb-6 italic text-sm">
                                // {{ endpoint.intent }}
                            </div>

                            <!-- Parameters -->
                            <div v-if="endpoint.input && endpoint.input.length > 0" class="mb-6">
                                <div class="text-[10px] font-mono text-upsilon-steel uppercase mb-2 tracking-widest font-bold">Injected Params</div>
                                <div class="grid grid-cols-1 gap-2">
                                    <div v-for="param in endpoint.input" :key="param.name" class="flex items-start gap-4 p-2 bg-upsilon-gunmetal/10 border border-upsilon-steel/10">
                                        <code class="text-upsilon-magenta font-bold font-mono text-xs">{{ param.name }}</code>
                                        <span class="text-white/60 text-xs font-mono">{{ param.description }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Output -->
                            <div v-if="endpoint.output">
                                <div class="text-[10px] font-mono text-upsilon-steel uppercase mb-2 tracking-widest font-bold">Standard Payload Output</div>
                                <pre class="bg-black/60 p-3 text-[10px] font-mono text-upsilon-lime leading-relaxed overflow-x-auto border border-upsilon-steel/10">{{ endpoint.output }}</pre>
                            </div>

                            <!-- Bottom Accent -->
                            <div class="absolute bottom-0 right-0 w-8 h-8 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity">
                                <div class="absolute bottom-0 right-0 w-4 h-4 border-b border-r border-upsilon-cyan"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="filteredEndpoints.length === 0" class="text-center py-20 text-upsilon-steel italic font-mono">
                    [ NO_MATCHES_FOUND ]
                </div>
            </div>
        </div>
    </TacticalLayout>
</template>

<style scoped>
.shadow-neon {
    box-shadow: 0 0 15px rgba(34, 211, 238, 0.3);
}
.shadow-glow-cyan {
    box-shadow: 0 0 10px rgba(34, 211, 238, 0.5);
}
.shadow-glow-magenta {
    box-shadow: 0 0 10px rgba(217, 70, 239, 0.5);
}
</style>
