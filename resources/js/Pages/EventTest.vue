<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';

const events = ref([]);

onMounted(() => {
    console.log('Listening to private channel: battle.42');
    
    window.Echo.channel('battle.42')
        .listen('.battle.event', (e) => {
            console.log('Received battle event:', e);
            events.value.unshift({
                id: Date.now(),
                data: e,
                timestamp: new Date().toLocaleTimeString()
            });
        })
        .error((error) => {
            console.error('Echo error:', error);
        });
});

onUnmounted(() => {
    window.Echo.leave('battle.42');
});
</script>

<template>
    <Head title="Echo Event Test" />

    <div class="min-h-screen bg-gray-900 text-white p-8 font-sans">
        <div class="max-w-4xl mx-auto">
            <header class="mb-8 border-b border-gray-700 pb-4 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-purple-500 bg-clip-text text-transparent">
                        Battle Event Listener
                    </h1>
                    <p class="text-gray-400 mt-2">Listening to private channel: <span class="text-blue-400 font-mono">battle.42</span></p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-sm text-green-400">Live</span>
                </div>
            </header>

            <main>
                <div v-if="events.length === 0" class="bg-gray-800 rounded-xl p-12 text-center border-2 border-dashed border-gray-700">
                    <p class="text-gray-500 text-lg">Waiting for events to arrive...</p>
                    <p class="text-sm text-gray-600 mt-2">Trigger an event to see it appear here.</p>
                </div>

                <div v-else class="space-y-4">
                    <div v-for="event in events" :key="event.id" 
                         class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden transition-all hover:border-blue-500/50 shadow-lg">
                        <div class="bg-gray-700/50 px-4 py-2 flex justify-between items-center border-b border-gray-700">
                            <span class="text-xs font-mono text-blue-300">.battle.event</span>
                            <span class="text-xs text-gray-400">{{ event.timestamp }}</span>
                        </div>
                        <div class="p-4">
                            <pre class="text-sm font-mono text-green-400 overflow-x-auto">{{ JSON.stringify(event.data, null, 2) }}</pre>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
pre::-webkit-scrollbar {
    height: 4px;
}
pre::-webkit-scrollbar-thumb {
    background: #4b5563;
    border-radius: 2px;
}
</style>
