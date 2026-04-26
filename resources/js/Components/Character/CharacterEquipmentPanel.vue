<!-- @spec-link [[ui_character_equipment_panel]] -->
<script setup>
import EquipmentSlotPill from './EquipmentSlotPill.vue';

const props = defineProps({
    equipment: {
        type: Object,
        default: () => ({})
    }
});

defineEmits(['manage']);
</script>

<template>
    <div class="grid grid-cols-1 gap-1.5">
        <EquipmentSlotPill 
            slot="armor" 
            :item="equipment.armor_item" 
            @click="$emit('manage', 'armor')"
        />
        <EquipmentSlotPill 
            slot="weapon" 
            :item="equipment.weapon_item" 
            @click="$emit('manage', 'weapon')"
        />
        <EquipmentSlotPill 
            slot="utility" 
            :item="equipment.utility_item" 
            @click="$emit('manage', 'utility')"
        />
    </div>
</template>
