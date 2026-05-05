<!-- @spec-link [[ui_theme]] -->
<script setup>
defineEmits(['click']);
</script>

<template>
    <button
        @click="$emit('click')"
        data-testid="shop-button"
        class="neon-shop-btn relative w-full px-6 py-8 flex flex-col items-center gap-3 group transition-all duration-300"
    >
        <!-- Corner accents -->
        <div class="absolute -top-px -left-px w-4 h-4 border-t-2 border-l-2 border-upsilon-magenta group-hover:border-upsilon-cyan transition-colors duration-500"></div>
        <div class="absolute -bottom-px -right-px w-4 h-4 border-b-2 border-r-2 border-upsilon-magenta group-hover:border-upsilon-cyan transition-colors duration-500"></div>

        <!-- Neon sign icon — stylised SHOP sigil -->
        <div class="relative flex flex-col items-center gap-1">
            <div class="text-upsilon-cyan font-scifi text-ui-xs uppercase tracking-[0.5em] animate-pulse">
                ◈ OPEN ◈
            </div>
            <div class="relative">
                <span class="text-upsilon-magenta font-scifi text-3xl font-bold uppercase tracking-[0.2em] neon-text-magenta group-hover:text-upsilon-cyan transition-colors duration-500">
                    SUPPLY
                </span>
            </div>
            <div class="relative">
                <span class="text-upsilon-cyan font-scifi text-3xl font-bold uppercase tracking-[0.2em] neon-text-cyan group-hover:text-upsilon-magenta transition-colors duration-500">
                    DEPOT
                </span>
            </div>
            <div class="text-upsilon-lime font-mono text-ui-xs uppercase tracking-[0.4em] mt-1 opacity-70 group-hover:opacity-100 transition-opacity">
                — FULL CATALOG —
            </div>
        </div>

        <!-- Neon bar bottom -->
        <div class="w-full h-px bg-gradient-to-r from-transparent via-upsilon-magenta to-transparent opacity-60 group-hover:opacity-100 transition-opacity duration-300"></div>
    </button>
</template>

<style scoped>
.neon-shop-btn {
    background: linear-gradient(135deg, rgba(26, 26, 30, 0.95) 0%, rgba(10, 10, 11, 0.98) 100%);
    border: 1px solid rgba(255, 0, 255, 0.3);
    box-shadow:
        0 0 8px rgba(255, 0, 255, 0.2),
        inset 0 0 20px rgba(255, 0, 255, 0.03);
}

.neon-shop-btn:hover {
    border-color: rgba(0, 242, 255, 0.5);
    box-shadow:
        0 0 15px rgba(0, 242, 255, 0.3),
        0 0 30px rgba(255, 0, 255, 0.15),
        inset 0 0 20px rgba(0, 242, 255, 0.05);
}

.neon-text-magenta {
    text-shadow:
        0 0 6px rgba(255, 0, 255, 0.8),
        0 0 12px rgba(255, 0, 255, 0.4);
}

.neon-text-cyan {
    text-shadow:
        0 0 6px rgba(0, 242, 255, 0.8),
        0 0 12px rgba(0, 242, 255, 0.4);
}
</style>
