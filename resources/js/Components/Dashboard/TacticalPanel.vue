<!-- Universal slide-out panel — replaces all <dialog>-based modals.
     Uses Teleport+div pattern (same as original DiagnosticTerminal) for
     Playwright compatibility. @spec-link [[ui_dashboard]] -->
<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import CharacterPanel from './panels/CharacterPanel.vue';
import ShopPanel from './panels/ShopPanel.vue';
import InventoryPanel from './panels/InventoryPanel.vue';
import IdentityPanel from './panels/IdentityPanel.vue';

const props = defineProps({
    mode:        { type: String, default: null },   // 'character'|'shop'|'inventory'|'identity'|null
    characterId: { type: String, default: null },
    user:        { type: Object, default: null },
});

const emit = defineEmits(['close']);

const isOpen = computed(() => props.mode !== null);

function onKeydown(e) {
    if (e.key === 'Escape' && isOpen.value) emit('close');
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onUnmounted(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <!-- Panel -->
        <div
            data-testid="tactical-panel"
            class="fixed inset-y-0 right-0 z-40 w-[880px] flex flex-col
                   bg-upsilon-gunmetal/90 backdrop-blur-xl
                   border-l border-upsilon-cyan/30
                   border-t-2 border-t-upsilon-cyan/60"
            :class="isOpen ? 'translate-x-0' : 'translate-x-full'"
            style="transition: transform 280ms linear;"
        >
            <CharacterPanel
                v-if="mode === 'character'"
                :character-id="characterId"
                :user="user"
                @close="$emit('close')"
            />
            <ShopPanel
                v-else-if="mode === 'shop'"
                :user="user"
                @close="$emit('close')"
            />
            <InventoryPanel
                v-else-if="mode === 'inventory'"
                @close="$emit('close')"
            />
            <IdentityPanel
                v-else-if="mode === 'identity'"
                :user="user"
                @close="$emit('close')"
            />
        </div>

        <!-- Backdrop -->
        <Transition name="tp-backdrop">
            <div
                v-if="isOpen"
                class="fixed inset-0 z-30 bg-black/40 backdrop-blur-sm"
                @click="$emit('close')"
            ></div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.tp-backdrop-enter-active,
.tp-backdrop-leave-active {
    transition: opacity 200ms linear;
}
.tp-backdrop-enter-from,
.tp-backdrop-leave-to {
    opacity: 0;
}
</style>
