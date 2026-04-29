<!-- Character detail + loadout management modal.
     Left: stats + equipment/skill slots. Right: swap UI for selected slot.
     @spec-link [[ui_character_full_stat_panel]]
     @spec-link [[entity_character_skill_inventory]] -->
<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import ModalBox from '@/Components/ModalBox.vue';
import SkillDetail from '@/Components/Skill/SkillDetail.vue';
import SkillSlotPill from '@/Components/Skill/SkillSlotPill.vue';
import SkillCard from '@/Components/Skill/SkillCard.vue';
import EquipmentSlotPill from '@/Components/Character/EquipmentSlotPill.vue';
import inventoryService from '@/services/inventory';
import skillService from '@/services/skill';
import auth from '@/services/auth';

const props = defineProps({
    show: { type: Boolean, default: false },
    characterId: { type: String, default: null },
    user: { type: Object, default: null },
});

const emit = defineEmits(['close', 'roulette-click', 'credits-updated']);

// --- Data ---
const character  = ref(null);
const skills     = ref([]);   // CharacterSkill[]
const equipment  = ref({});   // { armor, weapon, utility } — InventoryItemResource | null
const inventory  = ref([]);   // InventoryItemResource[] (full player inventory)
const loading    = ref(false);
const error      = ref('');

// Selection state
const selectedType = ref(null); // 'equipment' | 'skill'
const selectedSlot = ref(null); // slot string ('armor'|'weapon'|'utility') OR skill slot index (int)

// Action state
const actionLoading = ref(false);
const actionError   = ref('');

// --- Computed ---
const skillSlots = computed(() => character.value?.skill_slots ?? 1);

const equippedSkills = computed(() => skills.value.filter(s => s.equipped));
const unequippedSkills = computed(() => skills.value.filter(s => !s.equipped));

function skillInSlot(slotIndex) {
    // slot 0 → first equipped skill, etc.
    return equippedSkills.value[slotIndex] ?? null;
}

const currentEquipment = computed(() => {
    if (selectedType.value !== 'equipment' || !selectedSlot.value) return null;
    return equipment.value[selectedSlot.value] ?? null;
});

const compatibleInventory = computed(() => {
    if (selectedType.value !== 'equipment' || !selectedSlot.value) return [];
    return inventory.value.filter(i => i.shop_item?.slot === selectedSlot.value);
});

const currentSkillInSelectedSlot = computed(() => {
    if (selectedType.value !== 'skill' || selectedSlot.value === null) return null;
    return skillInSlot(selectedSlot.value);
});

const stats = computed(() => {
    if (!character.value) return [];
    const KEYS = ['hp','mp','sp','attack','defense','movement','jump_height','crit_chance','crit_damage'];
    const labels = { hp:'HP', mp:'MP', sp:'SP', attack:'ATK', defense:'DEF', movement:'MOV', jump_height:'JMP', crit_chance:'CRT%', crit_damage:'CRT+' };
    return KEYS.map(k => ({ key: k, label: labels[k], value: character.value[k] ?? 0 }));
});

// --- Data loading ---
async function load() {
    if (!props.characterId) return;
    loading.value = true;
    error.value = '';
    selectedType.value = null;
    selectedSlot.value = null;
    try {
        const [charData, skillsData, equipData, invData] = await Promise.all([
            auth.get(`/profile/character/${props.characterId}`),
            skillService.listCharacterSkills(props.characterId),
            inventoryService.getEquipment(props.characterId),
            inventoryService.listInventory(),
        ]);
        character.value  = charData;
        skills.value     = Array.isArray(skillsData) ? skillsData : [];
        // equipData: { character_id, armor, weapon, utility }
        equipment.value  = {
            armor:   equipData?.armor   ?? null,
            weapon:  equipData?.weapon  ?? null,
            utility: equipData?.utility ?? null,
        };
        inventory.value  = Array.isArray(invData) ? invData : [];
    } catch (e) {
        error.value = 'Failed to load character data.';
    } finally {
        loading.value = false;
    }
}

watch(() => props.show, (v) => { if (v) load(); });
watch(() => props.characterId, () => { if (props.show) load(); });

// --- Slot selection ---
function selectEquipmentSlot(slot) {
    actionError.value = '';
    if (selectedType.value === 'equipment' && selectedSlot.value === slot) {
        selectedType.value = null; selectedSlot.value = null;
    } else {
        selectedType.value = 'equipment'; selectedSlot.value = slot;
    }
}

function selectSkillSlot(index) {
    actionError.value = '';
    if (selectedType.value === 'skill' && selectedSlot.value === index) {
        selectedType.value = null; selectedSlot.value = null;
    } else {
        selectedType.value = 'skill'; selectedSlot.value = index;
    }
}

// --- Equipment actions ---
async function equipItem(item) {
    actionLoading.value = true; actionError.value = '';
    try {
        await inventoryService.equip(props.characterId, item.id);
        await load();
    } catch (e) {
        actionError.value = e?.message ?? 'Link failed.';
    } finally { actionLoading.value = false; }
}

async function unequipSlot(slot) {
    actionLoading.value = true; actionError.value = '';
    try {
        await inventoryService.unequip(props.characterId, slot);
        await load();
    } catch (e) {
        actionError.value = e?.message ?? 'Sever failed.';
    } finally { actionLoading.value = false; }
}

// --- Skill actions ---
async function equipSkill(skill) {
    actionLoading.value = true; actionError.value = '';
    try {
        await skillService.equip(props.characterId, skill.id);
        await load();
    } catch (e) {
        actionError.value = e?.message ?? 'Skill link failed.';
    } finally { actionLoading.value = false; }
}

async function unequipSkill(skill) {
    actionLoading.value = true; actionError.value = '';
    try {
        await skillService.unequip(props.characterId, skill.id);
        await load();
    } catch (e) {
        actionError.value = e?.message ?? 'Skill sever failed.';
    } finally { actionLoading.value = false; }
}

const slotNames = ['armor', 'weapon', 'utility'];
const slotIcons = { armor: '◈', weapon: '⚔', utility: '◉' };
</script>

<template>
    <ModalBox
        :show="show"
        :title="character?.name ?? 'Combatant'"
        subtitle="Loadout management — select a slot to manage"
        max-width="5xl"
        @close="$emit('close')"
    >
        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-16">
            <span class="font-mono text-[10px] text-upsilon-cyan uppercase tracking-widest animate-pulse">
                Synchronizing combatant data...
            </span>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="py-8 text-center font-mono text-[9px] text-upsilon-magenta uppercase">
            {{ error }}
        </div>

        <div v-else-if="character" class="flex gap-6 min-h-[500px]">

            <!-- ===== LEFT PANE: stats + loadout ===== -->
            <div class="w-64 shrink-0 flex flex-col gap-4 overflow-y-auto max-h-[70vh]">

                <!-- Stats compact grid -->
                <div class="space-y-1">
                    <div class="text-[8px] font-mono text-upsilon-steel uppercase tracking-[0.4em] mb-2">BIOMETRIC DATA</div>
                    <div class="grid grid-cols-3 gap-1">
                        <div v-for="stat in stats" :key="stat.key"
                            class="flex flex-col items-center py-1.5 bg-black/40 border border-upsilon-steel/10">
                            <span class="font-mono text-[7px] text-upsilon-steel uppercase">{{ stat.label }}</span>
                            <span class="font-scifi text-[12px] text-white font-bold">{{ stat.value }}</span>
                        </div>
                    </div>
                </div>

                <div class="border-t border-upsilon-steel/20"></div>

                <!-- Equipment slots -->
                <div>
                    <div class="text-[8px] font-mono text-upsilon-steel uppercase tracking-[0.4em] mb-2">HARDWARE LINK</div>
                    <div class="space-y-1">
                        <div
                            v-for="slot in slotNames"
                            :key="slot"
                            @click="selectEquipmentSlot(slot)"
                            class="flex items-center gap-2 p-2 border cursor-pointer transition-all duration-150"
                            :class="selectedType === 'equipment' && selectedSlot === slot
                                ? 'bg-upsilon-cyan/10 border-upsilon-cyan/60'
                                : 'bg-black/30 border-upsilon-steel/20 hover:border-upsilon-cyan/30'"
                        >
                            <span class="text-xs text-upsilon-steel">{{ slotIcons[slot] }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="font-mono text-[7px] text-upsilon-steel uppercase">{{ slot }}</div>
                                <div class="font-scifi text-[9px] uppercase truncate"
                                    :class="equipment[slot] ? 'text-white' : 'text-upsilon-steel/40'">
                                    {{ equipment[slot]?.shop_item?.name ?? 'Empty' }}
                                </div>
                            </div>
                            <span class="text-upsilon-steel/40 text-xs">›</span>
                        </div>
                    </div>
                </div>

                <div class="border-t border-upsilon-steel/20"></div>

                <!-- Skill slots -->
                <div>
                    <div class="flex items-baseline justify-between mb-2">
                        <span class="text-[8px] font-mono text-upsilon-steel uppercase tracking-[0.4em]">SKILL LOADOUT</span>
                        <span class="font-mono text-[7px] text-upsilon-steel/60">
                            {{ equippedSkills.length }}/{{ skillSlots }}
                        </span>
                    </div>
                    <div class="space-y-1">
                        <SkillSlotPill
                            v-for="i in skillSlots"
                            :key="i - 1"
                            :slot="i - 1"
                            :skill="skillInSlot(i - 1)"
                            :selected="selectedType === 'skill' && selectedSlot === (i - 1)"
                            @click="selectSkillSlot(i - 1)"
                        />
                    </div>
                </div>

                <!-- Roulette button -->
                <div v-if="character.roulette_available" class="mt-auto pt-2 border-t border-upsilon-steel/20">
                    <button
                        @click="$emit('roulette-click', character.id)"
                        class="w-full py-3 border border-upsilon-magenta text-upsilon-magenta font-scifi text-[9px] uppercase tracking-[0.3em] hover:bg-upsilon-magenta hover:text-white transition-all duration-300 animate-pulse"
                        style="box-shadow: 0 0 8px rgba(255,0,255,0.3);"
                    >
                        ◈ SKILL ROULETTE ◈
                    </button>
                    <div class="font-mono text-[7px] text-upsilon-magenta/60 text-center mt-1 uppercase tracking-widest">
                        One-time acquisition available
                    </div>
                </div>
            </div>

            <!-- ===== RIGHT PANE: swap UI ===== -->
            <div class="flex-1 overflow-y-auto max-h-[70vh] border-l border-upsilon-steel/20 pl-6">

                <!-- No selection -->
                <div v-if="!selectedType" class="h-full flex items-center justify-center">
                    <p class="font-mono text-[10px] text-upsilon-steel/50 uppercase tracking-widest">
                        Select a slot to manage it.
                    </p>
                </div>

                <!-- Action error -->
                <div v-if="actionError" class="mb-4 px-3 py-2 border border-upsilon-magenta/50 bg-upsilon-magenta/10 font-mono text-[9px] text-upsilon-magenta uppercase">
                    ⚠ {{ actionError }}
                </div>

                <!-- ===== EQUIPMENT SLOT UI ===== -->
                <div v-if="selectedType === 'equipment'" class="space-y-6">
                    <div>
                        <div class="text-[8px] font-mono text-upsilon-steel uppercase tracking-[0.4em] mb-3">
                            ACTIVE LINK — {{ selectedSlot?.toUpperCase() }}
                        </div>
                        <div v-if="currentEquipment" class="p-3 bg-black/40 border border-upsilon-steel/20 space-y-3">
                            <div>
                                <div class="font-scifi text-sm text-white uppercase tracking-widest">
                                    {{ currentEquipment.shop_item?.name }}
                                </div>
                                <div class="font-mono text-[8px] text-upsilon-steel uppercase mt-1">
                                    Currently linked to this character
                                </div>
                            </div>
                            <button
                                :disabled="actionLoading"
                                @click="unequipSlot(selectedSlot)"
                                class="px-4 py-1.5 border border-upsilon-magenta/40 text-upsilon-magenta font-scifi text-[9px] uppercase hover:bg-upsilon-magenta hover:text-white transition-all disabled:opacity-40"
                            >
                                Terminate Link
                            </button>
                        </div>
                        <div v-else class="p-3 border border-dashed border-upsilon-steel/20 font-mono text-[9px] text-upsilon-steel/40 uppercase">
                            No hardware linked to this slot.
                        </div>
                    </div>

                    <div>
                        <div class="text-[8px] font-mono text-upsilon-steel uppercase tracking-[0.4em] mb-3">
                            COMPATIBLE MANIFESTS
                        </div>
                        <div v-if="!compatibleInventory.length" class="py-6 text-center border border-dashed border-upsilon-steel/10">
                            <span class="font-mono text-[9px] text-upsilon-steel/40 uppercase">No compatible hardware in inventory.</span>
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="item in compatibleInventory"
                                :key="item.id"
                                class="flex items-center justify-between p-3 bg-black/40 border border-upsilon-steel/20 hover:border-upsilon-cyan/40 transition-all"
                            >
                                <div>
                                    <div class="font-scifi text-[10px] text-white uppercase tracking-widest">
                                        {{ item.shop_item?.name }}
                                    </div>
                                    <div class="font-mono text-[7px] mt-0.5"
                                        :class="item.equipped_on ? 'text-upsilon-magenta' : 'text-upsilon-lime'">
                                        {{ item.equipped_on ? 'Linked to ' + item.equipped_on.name : 'Available' }}
                                    </div>
                                </div>
                                <button
                                    :disabled="actionLoading"
                                    @click="equipItem(item)"
                                    class="px-3 py-1 border border-upsilon-cyan/40 text-upsilon-cyan font-scifi text-[8px] uppercase hover:bg-upsilon-cyan hover:text-black transition-all disabled:opacity-40"
                                >
                                    Link
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== SKILL SLOT UI ===== -->
                <div v-else-if="selectedType === 'skill'" class="space-y-6">

                    <!-- Current skill in this slot -->
                    <div>
                        <div class="text-[8px] font-mono text-upsilon-steel uppercase tracking-[0.4em] mb-3">
                            SLOT {{ selectedSlot + 1 }} — CURRENT
                        </div>
                        <div v-if="currentSkillInSelectedSlot" class="space-y-3">
                            <SkillDetail :skill="currentSkillInSelectedSlot" />
                            <button
                                :disabled="actionLoading"
                                @click="unequipSkill(currentSkillInSelectedSlot)"
                                class="px-4 py-1.5 border border-upsilon-magenta/40 text-upsilon-magenta font-scifi text-[9px] uppercase hover:bg-upsilon-magenta hover:text-white transition-all disabled:opacity-40"
                            >
                                Remove from Slot
                            </button>
                        </div>
                        <div v-else class="p-3 border border-dashed border-upsilon-steel/20 font-mono text-[9px] text-upsilon-steel/40 uppercase">
                            Empty slot — no skill active.
                        </div>
                    </div>

                    <!-- Available skills to equip into this slot -->
                    <div v-if="unequippedSkills.length">
                        <div class="text-[8px] font-mono text-upsilon-steel uppercase tracking-[0.4em] mb-3">
                            AVAILABLE PROTOCOLS
                        </div>
                        <div class="space-y-1">
                            <div
                                v-for="sk in unequippedSkills"
                                :key="sk.id"
                                class="flex items-center gap-2"
                            >
                                <div class="flex-1">
                                    <SkillCard :skill="sk" :equipped="false" />
                                </div>
                                <button
                                    :disabled="actionLoading || equippedSkills.length >= skillSlots"
                                    @click="equipSkill(sk)"
                                    class="shrink-0 px-3 py-1.5 border border-upsilon-cyan/40 text-upsilon-cyan font-scifi text-[8px] uppercase hover:bg-upsilon-cyan hover:text-black transition-all disabled:opacity-40"
                                >
                                    Equip
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="!currentSkillInSelectedSlot" class="py-4 text-center border border-dashed border-upsilon-steel/10">
                        <span class="font-mono text-[9px] text-upsilon-steel/40 uppercase">No unequipped skills in inventory. Use the Roulette to acquire one.</span>
                    </div>
                </div>
            </div>
        </div>
    </ModalBox>
</template>
