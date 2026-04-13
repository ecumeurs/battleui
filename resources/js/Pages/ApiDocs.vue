<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import TacticalLayout from '@/Layouts/TacticalLayout.vue';
import axios from 'axios';
import { getAuthUser } from '@/services/auth';

const user = ref(null);
const rawEndpoints = ref([]);
const websockets = ref(null);
const envelope = ref(null);
const workflow = ref(null);
const dtoRegistry = ref({});
const loading = ref(true);
const error = ref(null);
const searchQuery = ref('');

const groupedEndpoints = computed(() => {
    const groups = {};
    
    rawEndpoints.value.forEach(endpoint => {
        // Preference: Custom tag, then URI segment
        let groupKey = endpoint.tag;
        if (!groupKey) {
            const segments = endpoint.uri.split('/');
            groupKey = segments[2] || 'general';
        }
        
        const groupName = groupKey.charAt(0).toUpperCase() + groupKey.slice(1);
        
        if (!groups[groupKey]) {
            groups[groupKey] = {
                id: groupKey,
                name: groupName + (groupKey.toLowerCase() === 'admin' ? ' Panel' : ' Services'),
                endpoints: []
            };
        }
        groups[groupKey].endpoints.push(endpoint);
    });

    return Object.values(groups);
});

const filteredGroups = computed(() => {
    if (!searchQuery.value) return groupedEndpoints.value;
    const q = searchQuery.value.toLowerCase();
    
    return groupedEndpoints.value.map(group => {
        const matchingEndpoints = group.endpoints.filter(e => 
            e.uri.toLowerCase().includes(q) || 
            (e.intent && e.intent.toLowerCase().includes(q)) ||
            (e.atd_link && e.atd_link.toLowerCase().includes(q)) ||
            (e.tag && e.tag.toLowerCase().includes(q))
        );
        
        if (matchingEndpoints.length > 0 || group.name.toLowerCase().includes(q)) {
            return { ...group, endpoints: matchingEndpoints };
        }
        return null;
    }).filter(g => g !== null);
});

onMounted(async () => {
    user.value = getAuthUser();
    try {
        const response = await axios.get('/api/v1/help');
        const d = response.data.data;
        rawEndpoints.value = d.endpoints || [];
        websockets.value = d.websocket || null;
        envelope.value = d.envelope || null;
        workflow.value = d.workflow || null;
        dtoRegistry.value = d.dto_registry || {};
    } catch (err) {
        error.value = "CONNECTION_STRIKE: Failed to synchronize with Tactical Discovery Registry.";
    } finally {
        loading.value = false;
    }
});

const getVerbClass = (verb) => {
    if (Array.isArray(verb)) verb = verb[0];
    switch (verb?.toUpperCase()) {
        case 'GET': return 'text-upsilon-cyan border-upsilon-cyan/50';
        case 'POST': return 'text-upsilon-magenta border-upsilon-magenta/50';
        case 'PUT': return 'text-upsilon-lime border-upsilon-lime/50';
        case 'DELETE': return 'text-red-500 border-red-500/50';
        default: return 'text-white border-white/50';
    }
};

const formatVerb = (verb) => {
    return Array.isArray(verb) ? verb.join(' | ') : verb;
};

const getDto = (id) => {
    if (!id) return null;
    // Strip backticks or brackets if they leaked into the ID
    const cleanId = id.replace(/[\[\]`]/g, '').trim();
    return dtoRegistry.value[cleanId] || null;
};
</script>

<template>
    <Head title="Tactical API Registry" />

    <TacticalLayout :user="user">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-scifi text-2xl text-white tracking-widest uppercase">
                    API_<span class="text-upsilon-cyan">DISCOVERY</span>_HUB
                </h2>
                <div class="text-xs font-mono text-upsilon-cyan/80 animate-pulse">
                    [ NODE_STATUS: SYNCED ]
                </div>
            </div>
        </template>

        <div class="py-6 px-4 max-w-7xl mx-auto">
            
            <div v-if="loading" class="flex flex-col items-center justify-center py-40">
                <div class="w-16 h-16 border-4 border-upsilon-cyan/30 border-t-upsilon-cyan rounded-full animate-spin mb-4 shadow-glow-cyan"></div>
                <div class="font-mono text-upsilon-cyan tracking-widest uppercase animate-pulse">Initializing documentation streams...</div>
            </div>

            <div v-else-if="error" class="bg-red-900/40 border border-red-500/80 p-8 text-center my-20">
                <div class="text-red-500 font-bold mb-4 uppercase tracking-tighter text-2xl">System Critical Malfunction</div>
                <div class="text-white font-mono text-lg">{{ error }}</div>
            </div>

            <div v-else class="space-y-16">
                
                <!-- 1. Operational Workflows -->
                <section v-if="workflow" class="border border-upsilon-cyan/20 bg-upsilon-gunmetal/20 p-6 shadow-neon">
                    <h3 class="font-scifi text-upsilon-cyan text-sm mb-4 tracking-tighter uppercase border-b border-upsilon-cyan/30 pb-2">
                        // OPERATIONAL_PROCEDURES
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div v-for="(steps, name) in workflow" :key="name" class="space-y-2">
                            <div class="text-[10px] font-mono text-upsilon-magenta uppercase tracking-widest">{{ name }} process</div>
                            <div class="text-white/90 font-mono text-xs leading-relaxed border-l-2 border-upsilon-magenta/50 pl-3">
                                {{ steps }}
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 2. Standard Envelope -->
                <section v-if="envelope" class="border border-upsilon-cyan/20 bg-upsilon-void p-6 relative">
                    <div class="absolute -top-3 left-6 bg-upsilon-void px-3 text-[10px] font-mono text-upsilon-cyan uppercase border border-upsilon-cyan/30 shadow-glow-cyan">
                        Global_Result_Envelope
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                        <div class="space-y-3">
                            <div v-for="(desc, field) in envelope" :key="field" class="flex items-start gap-4">
                                <code class="text-upsilon-cyan font-bold font-mono text-xs min-w-[100px]">{{ field }}</code>
                                <span class="text-white/80 text-[11px] font-sans leading-tight">{{ desc }}</span>
                            </div>
                        </div>
                        <div class="bg-black/40 p-4 border border-upsilon-cyan/10 rounded">
                            <pre class="text-[10px] font-mono text-upsilon-lime leading-tight">
{
  "request_id": "018f...",
  "message": "Operation success",
  "success": true,
  "data": { ... },
  "meta": { ... }
}</pre>
                        </div>
                    </div>
                </section>

                <!-- 3. API Endpoints -->
                <div>
                    <!-- Search Bar -->
                    <div class="mb-12 relative group max-w-3xl mx-auto">
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Filter tactical endpoints..."
                            class="w-full bg-upsilon-gunmetal/30 border border-upsilon-cyan/30 rounded-none px-6 py-4 font-mono text-upsilon-cyan focus:border-upsilon-cyan focus:ring-0 transition-all placeholder:text-upsilon-cyan/40 text-lg shadow-inner"
                        />
                        <div class="absolute inset-y-0 right-6 flex items-center pointer-events-none opacity-60">
                            <span class="text-xs font-mono text-white">[ SEARCH_QUERY ]</span>
                        </div>
                        <div class="absolute bottom-0 left-0 h-[2px] bg-upsilon-cyan w-0 group-focus-within:w-full transition-all duration-700 shadow-glow-cyan"></div>
                    </div>

                    <div class="space-y-20">
                        <div v-for="group in filteredGroups" :key="group.id" class="relative">
                            <h3 class="text-2xl font-scifi text-white mb-8 uppercase tracking-widest flex items-center group">
                                <span class="w-3 h-3 bg-upsilon-magenta mr-4 shadow-glow-magenta group-hover:scale-125 transition-transform"></span>
                                {{ group.name }}
                                <div class="ml-6 flex-grow h-[1px] bg-upsilon-cyan/10"></div>
                            </h3>

                            <div class="grid grid-cols-1 gap-8">
                                <div v-for="(endpoint, idx) in group.endpoints" :key="idx" 
                                    class="bg-upsilon-gunmetal/10 border border-upsilon-cyan/10 p-6 relative group hover:bg-upsilon-gunmetal/20 hover:border-upsilon-cyan/50 transition-all duration-300"
                                >
                                    <!-- Header -->
                                    <div class="flex flex-wrap items-center gap-6 mb-4">
                                        <span :class="['font-mono font-bold px-4 py-1 border text-sm uppercase tracking-widest', getVerbClass(endpoint.methods)]">
                                            {{ formatVerb(endpoint.methods) }}
                                        </span>
                                        <code class="text-white font-mono text-xl tracking-tight bg-black/40 px-3 py-1 border border-upsilon-cyan/10">
                                            {{ endpoint.uri }}
                                        </code>
                                        
                                        <div v-if="endpoint.auth?.required" class="flex items-center gap-2 text-[10px] font-mono text-upsilon-magenta bg-upsilon-magenta/20 px-2 py-1 border border-upsilon-magenta/40 shadow-glow-magenta">
                                            <span class="animate-pulse">●</span> AUTH_REQUIRED
                                            <span v-if="endpoint.auth?.admin_only" class="border-l border-upsilon-magenta/30 pl-2 uppercase font-bold text-[9px]">ROOT_ONLY</span>
                                        </div>
                                    </div>

                                    <!-- Intent -->
                                    <div class="text-upsilon-cyan/90 font-sans mb-8 italic text-base border-l-2 border-upsilon-cyan/40 pl-4 py-1 bg-upsilon-cyan/5">
                                        {{ endpoint.intent }}
                                    </div>

                                    <!-- Data Models (In/Out) -->
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                                        <!-- Inputs -->
                                        <div v-if="endpoint.parameters && endpoint.parameters.length > 0">
                                            <div class="text-[10px] font-mono text-upsilon-cyan/80 uppercase mb-3 tracking-[0.2em] font-bold">Input_Contracts</div>
                                            <div class="grid grid-cols-1 gap-2">
                                                <div v-for="param in endpoint.parameters" :key="param.name" class="flex flex-col p-3 bg-upsilon-void/80 border border-upsilon-cyan/20">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <code class="text-upsilon-magenta font-bold font-mono text-xs">{{ param.name }}</code>
                                                        <span class="text-[9px] font-mono text-upsilon-cyan bg-upsilon-cyan/10 px-1 uppercase">{{ param.type }}</span>
                                                    </div>
                                                    <div class="text-white/80 text-[9px] font-mono truncate">{{ param.constraints }}</div>
                                                    <div v-if="param.required" class="mt-1 text-[8px] font-mono text-upsilon-magenta uppercase font-bold">Required</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Output DTO -->
                                        <div v-if="endpoint.output">
                                            <div class="text-[10px] font-mono text-upsilon-cyan/80 uppercase mb-3 tracking-[0.2em] font-bold">Output_Payload</div>
                                            <div v-if="getDto(endpoint.output)" class="bg-upsilon-void/80 border border-upsilon-lime/40 p-3 shadow-glow-lime">
                                                <div class="text-[10px] font-mono text-upsilon-lime mb-2 border-b border-upsilon-lime/20 pb-1 flex justify-between">
                                                    <span>DTO: {{ endpoint.output }}</span>
                                                    <span class="animate-pulse text-xs">✓</span>
                                                </div>
                                                <div class="space-y-1">
                                                    <div v-for="prop in getDto(endpoint.output).properties" :key="prop.name" class="flex items-center justify-between">
                                                        <code class="text-white font-mono text-[10px]">{{ prop.name }}</code>
                                                        <span class="text-upsilon-lime font-mono text-[9px] uppercase">{{ prop.type }}</span>
                                                    </div>
                                                    <div v-if="getDto(endpoint.output).properties.length === 0" class="text-[10px] font-mono text-white/60 italic">
                                                        [ COMPLEX_JSON_PAYLOAD ]
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else class="text-xs font-mono text-upsilon-magenta italic bg-upsilon-magenta/5 p-3 border border-upsilon-magenta/20">
                                                DTO [[{{ endpoint.output }}]] pending registration.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ATD Link -->
                                    <div v-if="endpoint.atd_link" class="absolute top-6 right-6 opacity-40 group-hover:opacity-100 transition-opacity">
                                        <div class="text-[9px] font-mono text-white flex flex-col items-end">
                                            <span class="text-white/60">TRACE_ID</span>
                                            <span class="text-upsilon-cyan font-bold">[[{{ endpoint.atd_link }}]]</span>
                                        </div>
                                    </div>

                                    <!-- Corner Decals -->
                                    <div class="absolute top-0 right-0 w-4 h-4 border-t border-r border-upsilon-cyan/20 group-hover:border-upsilon-cyan/60"></div>
                                    <div class="absolute bottom-0 left-0 w-4 h-4 border-b border-l border-upsilon-cyan/20 group-hover:border-upsilon-cyan/60"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. WebSocket Registry -->
                <section v-if="websockets" class="space-y-12 pb-20">
                    <div class="flex items-center gap-6">
                        <div class="h-[1px] flex-grow bg-gradient-to-r from-transparent to-upsilon-cyan opacity-40"></div>
                        <h3 class="text-2xl font-scifi text-upsilon-cyan tracking-widest uppercase flex items-center">
                            W_SOCKET_<span class="text-white">REGISTRY</span>
                        </h3>
                        <div class="h-[1px] flex-grow bg-gradient-to-l from-transparent to-upsilon-cyan opacity-40"></div>
                    </div>

                    <div class="bg-upsilon-void/80 border border-upsilon-cyan/40 p-8 relative overflow-hidden shadow-neon">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-upsilon-cyan/10 -rotate-45 translate-x-16 -translate-y-16 pointer-events-none"></div>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                            <!-- Connection Details -->
                            <div>
                                <h4 class="text-xs font-mono text-upsilon-magenta mb-6 uppercase tracking-widest font-bold">Connection_Protocol</h4>
                                <div class="space-y-4">
                                    <div class="p-3 bg-upsilon-gunmetal/30 border-l-2 border-upsilon-magenta/60">
                                        <div class="text-[10px] font-mono text-upsilon-magenta/80 mb-1">HANDSHAKE</div>
                                        <div class="text-xs text-white leading-tight italic">{{ websockets.handshake }}</div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-[10px] font-mono">
                                        <div class="text-white/60">PROTOCOL</div><div class="text-white">{{ websockets.server.protocol }}</div>
                                        <div class="text-white/60">HOST</div><div class="text-white font-bold">{{ websockets.server.host }}</div>
                                        <div class="text-white/60">PORT</div><div class="text-white font-bold">{{ websockets.server.port }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Channels -->
                            <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div v-for="channel in websockets.channels" :key="channel.pattern" class="space-y-6">
                                    <div>
                                        <code class="text-upsilon-cyan font-bold font-mono text-sm block mb-1">/ {{ channel.pattern }}</code>
                                        <p class="text-[11px] text-white/90 leading-relaxed font-sans">{{ channel.description }}</p>
                                    </div>

                                    <div class="space-y-4">
                                        <div v-for="event in channel.events" :key="event.name" class="p-4 bg-upsilon-gunmetal/20 border border-upsilon-cyan/20 group/event hover:border-upsilon-magenta/50 transition-all">
                                            <div class="text-[10px] font-mono text-upsilon-magenta mb-1 font-bold tracking-widest">{{ event.name }}</div>
                                            <p class="text-[10px] text-white/80 mb-3">{{ event.description }}</p>
                                            <div class="text-[9px] font-mono">
                                                <span class="text-white/60 uppercase mr-2">Core Payload:</span>
                                                <code class="text-upsilon-lime font-bold">{{ JSON.stringify(event.payload) }}</code>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-[9px] font-mono text-upsilon-cyan animate-pulse italic">
                                        // TRIGGER: {{ channel.when }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </TacticalLayout>
</template>

<style scoped>
.shadow-neon {
    box-shadow: 0 0 20px rgba(0, 242, 255, 0.15);
}
.shadow-glow-cyan {
    box-shadow: 0 0 10px rgba(0, 242, 255, 0.4);
}
.shadow-glow-magenta {
    box-shadow: 0 0 10px rgba(255, 0, 255, 0.4);
}
.shadow-glow-lime {
    box-shadow: 0 0 10px rgba(57, 255, 19, 0.2);
}

::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: theme('colors.upsilon.void');
}
::-webkit-scrollbar-thumb {
    background: theme('colors.upsilon.cyan');
    opacity: 0.5;
    border-radius: 3px;
}
::-webkit-scrollbar-thumb:hover {
    background: theme('colors.upsilon.cyan');
    opacity: 1;
}
</style>
