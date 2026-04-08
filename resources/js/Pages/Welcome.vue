<!-- @spec-link [[ui_landing]] -->
<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Welcome to Upsilon" />

    <div class="relative min-h-screen bg-upsilon-void overflow-hidden flex items-center justify-center font-sans selection:bg-upsilon-cyan selection:text-upsilon-void">
        <!-- Background Layer with Hero Texture -->
        <div class="absolute inset-0 bg-hero-bg bg-cover bg-center opacity-60"></div>
        
        <!-- Animated Scanlines Layout -->
        <div class="absolute inset-0 pointer-events-none opacity-20 bg-[linear-gradient(rgba(18,16,16,0)_50%,rgba(0,0,0,0.25)_50%),linear-gradient(90deg,rgba(255,0,0,0.06),rgba(0,255,0,0.02),rgba(0,0,255,0.06))] bg-[length:100%_2px,3px_100%]"></div>

        <!-- Rust Overlay -->
        <div class="absolute inset-0 bg-panel-texture bg-repeat opacity-40"></div>

        <!-- Grid/HUD Decorations -->
        <div class="absolute top-0 left-0 w-full h-full border-[20px] border-upsilon-gunmetal/50 pointer-events-none">
            <div class="absolute top-4 left-4 w-12 h-12 border-t-2 border-l-2 border-upsilon-cyan shadow-glow-cyan"></div>
            <div class="absolute top-4 right-4 w-12 h-12 border-t-2 border-r-2 border-upsilon-cyan shadow-glow-cyan"></div>
            <div class="absolute bottom-4 left-4 w-12 h-12 border-b-2 border-l-2 border-upsilon-cyan shadow-glow-cyan"></div>
            <div class="absolute bottom-4 right-4 w-12 h-12 border-b-2 border-r-2 border-upsilon-cyan shadow-glow-cyan"></div>
        </div>

        <!-- Main Content -->
        <main class="relative z-10 text-center max-w-4xl px-6">
            <div class="mb-2">
                <span class="font-mono text-upsilon-cyan text-xs tracking-[0.5em] uppercase animate-pulse">
                    [ ACCESSING ARENA_NODE_07 ]
                </span>
            </div>
            
            <h1 class="text-6xl md:text-8xl font-scifi font-bold text-white mb-4 tracking-tighter uppercase">
                UPSILON<span class="text-upsilon-cyan shadow-glow-cyan italic">BATTLE</span>
            </h1>

            <div class="h-1 w-32 bg-upsilon-magenta mx-auto mb-8 shadow-glow-magenta"></div>

            <p class="text-xl md:text-2xl text-upsilon-steel font-medium mb-12 max-w-2xl mx-auto leading-relaxed">
                Scavenge the wastes. Strategize your roster. Survive the arena.
                <br />
                <span class="text-white/80">Face the Bots or challenge other Survivors in the ultimate T-RPG skirmish.</span>
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                <!-- Register Button -->
                <Link 
                    href="/register"
                    class="group relative px-10 py-4 bg-upsilon-cyan text-upsilon-void font-scifi font-bold text-xl uppercase tracking-widest transition-all duration-300 hover:scale-105 hover:shadow-neon"
                >
                    <span class="relative z-10">Register</span>
                    <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-20 transition-opacity"></div>
                    <div class="absolute -top-1 -left-1 w-3 h-3 border-t-2 border-l-2 border-black"></div>
                    <div class="absolute -bottom-1 -right-1 w-3 h-3 border-b-2 border-r-2 border-black"></div>
                </Link>

                <!-- Login Button -->
                <Link 
                    href="/login"
                    class="group relative px-10 py-4 border-2 border-upsilon-magenta text-upsilon-magenta font-scifi font-bold text-xl uppercase tracking-widest transition-all duration-300 hover:scale-105 hover:bg-upsilon-magenta hover:text-white hover:shadow-glow-magenta"
                >
                    <span class="relative z-10">Login</span>
                    <div class="absolute -top-1 -right-1 w-3 h-3 border-t-2 border-r-2 border-upsilon-magenta group-hover:border-white"></div>
                    <div class="absolute -bottom-1 -left-1 w-3 h-3 border-b-2 border-l-2 border-upsilon-magenta group-hover:border-white"></div>
                </Link>
            </div>

            <!-- Bot Skirmish Stats -->
            <div class="mt-20 grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="p-4 border border-upsilon-steel/30 bg-upsilon-gunmetal/20 backdrop-blur-sm">
                    <div class="text-upsilon-cyan font-mono text-2xl mb-1">1v1 / 2v2</div>
                    <div class="text-upsilon-steel text-xs uppercase tracking-widest">Combat Modes</div>
                </div>
                <div class="p-4 border border-upsilon-steel/30 bg-upsilon-gunmetal/20 backdrop-blur-sm">
                    <div class="text-upsilon-magenta font-mono text-2xl mb-1">ARENA BOTS</div>
                    <div class="text-upsilon-steel text-xs uppercase tracking-widest">Neural Training</div>
                </div>
                <div class="p-4 border border-upsilon-steel/30 bg-upsilon-gunmetal/20 backdrop-blur-sm">
                    <div class="text-upsilon-lime font-mono text-2xl mb-1">WIN -> UP</div>
                    <div class="text-upsilon-steel text-xs uppercase tracking-widest">Stat Progression</div>
                </div>
            </div>
        </main>

        <!-- Footer Tech Info -->
        <footer class="absolute bottom-8 w-full px-12 flex justify-between items-end pointer-events-none">
            <div class="font-mono text-[10px] text-upsilon-steel uppercase leading-tight">
                system_v: 1.0.4-beta<br />
                status: tactical_bridge_active<br />
                location: wastleland_sector_9
            </div>
            <div class="font-mono text-[10px] text-upsilon-steel text-right uppercase leading-tight">
                copyright &copy; 2026<br />
                upsilon_heavy_industries<br />
                [ undocumented protocol ]
            </div>
        </footer>
    </div>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
