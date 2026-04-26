<!-- @spec-link [[ui_character_equipment_panel]] -->
<script setup>
import { computed } from 'vue';
import EquipmentSlotPill from '@/Components/Character/EquipmentSlotPill.vue';

const props = defineProps({
    show: Boolean,
    character: {
        type: Object,
        default: null
    },
    inventory: {
        type: Array,
        default: () => []
    },
    activeSlot: {
        type: String,
        default: null
    }
});

const emit = defineEmits(['close', 'equip', 'unequip']);

const compatibleItems = computed(() => {
    if (!props.activeSlot) return [];
    return props.inventory.filter(item => item.shop_item.slot === props.activeSlot);
});

const currentEquipped = computed(() => {
    if (!props.character || !props.activeSlot) return null;
    const equipment = props.character.equipment || {};
    return equipment[`${props.activeSlot}_item`];
});
</script>

<template>
    <div 
        v-if="show"
        class="fixed inset-0 z-50 flex justify-end"
        @click.self="$emit('close')"
    >
        <!-- Overlay backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>
        
        <!-- Drawer -->
        <div class="relative w-full max-w-md bg-upsilon-gunmetal border-l border-upsilon-cyan/30 shadow-[0_0_50px_rgba(0,0,242,0.2)] flex flex-col animate-slide-in">
            <!-- Header -->
            <div class="p-6 border-b border-upsilon-steel/20 flex justify-between items-center bg-black/40">
                <div>
                    <h2 class="font-scifi text-lg text-white uppercase tracking-[0.2em]">Hardware Interface</h2>
                    <p class="text-[9px] text-upsilon-cyan font-mono uppercase tracking-widest mt-1">Linking to: {{ character?.name }}</p>
                </div>
                <button @click="$emit('close')" class="text-upsilon-steel hover:text-white transition-colors p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6 space-y-8">
                <!-- Current Slot Status -->
                <section>
                    <h3 class="text-[8px] font-scifi text-upsilon-steel uppercase mb-4 tracking-widest">Active Link Status</h3>
                    <div class="p-4 bg-black/60 border border-upsilon-steel/10 relative group">
                        <div class="absolute top-0 left-0 w-1 h-1 border-t border-l border-upsilon-cyan"></div>
                        <EquipmentSlotPill 
                            :slot="activeSlot" 
                            :item="currentEquipped"
                        />
                        
                        <div v-if="currentEquipped" class="mt-4 flex justify-end">
                            <button 
                                @click="$emit('unequip', { characterId: character.id, slot: activeSlot })"
                                class="px-4 py-1.5 bg-upsilon-magenta/10 border border-upsilon-magenta/40 text-upsilon-magenta font-scifi text-[9px] uppercase hover:bg-upsilon-magenta hover:text-white transition-all"
                            >
                                Terminate Link
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Available Hardware -->
                <section>
                    <h3 class="text-[8px] font-scifi text-upsilon-steel uppercase mb-4 tracking-widest">Compatible Manifests</h3>
                    
                    <div v-if="compatibleItems.length === 0" class="py-12 text-center border border-dashed border-upsilon-steel/10 rounded">
                        <p class="text-upsilon-steel font-mono text-[10px] uppercase">No compatible hardware detected in cache.</p>
                    </div>

                    <div class="space-y-3">
                        <div 
                            v-for="item in compatibleItems" 
                            :key="item.id"
                            class="p-3 bg-black/40 border border-upsilon-steel/20 hover:border-upsilon-cyan/40 transition-all group flex justify-between items-center cursor-pointer"
                            @click="$emit('equip', { characterId: character.id, itemId: item.id })"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full shadow-[0_0_5px_currentColor]" :class="item.equipped_on ? 'bg-upsilon-magenta text-upsilon-magenta' : 'bg-upsilon-lime text-upsilon-lime'"></div>
                                <div>
                                    <div class="text-[11px] font-scifi uppercase text-white tracking-wider">{{ item.shop_item.name }}</div>
                                    <div v-if="item.equipped_on" class="text-[7px] text-upsilon-magenta font-mono uppercase">Occupied by: {{ item.equipped_on.name }}</div>
                                    <div v-else class="text-[7px] text-upsilon-lime font-mono uppercase">Available</div>
                                </div>
                            </div>
                            
                            <div class="text-upsilon-cyan opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="font-scifi text-[8px] uppercase tracking-tighter">Establish Link >></span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Footer Footer -->
            <div class="p-4 bg-black/80 border-t border-upsilon-steel/20 font-mono text-[7px] text-upsilon-steel/60 uppercase text-center tracking-[0.3em]">
                Secure hardware encryption active. Authorized users only.
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes slide-in {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}
.animate-slide-in {
    animation: slide-in 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
