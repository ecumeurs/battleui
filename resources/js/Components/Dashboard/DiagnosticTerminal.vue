<!-- @spec-link [[ui_diagnostic_terminal]]
     @spec-link [[req_ui_look_and_feel]]
     @spec-link [[ui_character_full_stat_panel]]
     @spec-link [[entity_character_skill_inventory]] -->
<script setup>
import { ref, computed, watch } from 'vue';
import { useDashboardState } from '@/Composables/useDashboardState';
import SkillDetail from '@/Components/Skill/SkillDetail.vue';
import SkillSlotPill from '@/Components/Skill/SkillSlotPill.vue';
import SkillCard from '@/Components/Skill/SkillCard.vue';
import EquipmentSlotPill from '@/Components/Character/EquipmentSlotPill.vue';
import inventoryService from '@/services/inventory';
import skillService from '@/services/skill';
import auth from '@/services/auth';

const props = defineProps({
    characterId: { type: String, default: null },
    user:        { type: Object, default: null },
});

const emit = defineEmits(['close', 'roulette-click', 'credits-updated']);

const { inventory, refresh: refreshState } = useDashboardState();

// --- Data ---
const character   = ref(null);
const skills      = ref([]);
const equipment   = ref({ armor: null, weapon: null, utility: null });
const loading     = ref(false);
const error       = ref('');

// Selection state
const selectedType = ref(null); // 'equipment' | 'skill'
const selectedSlot = ref(null);

// Action state
const actionLoading = ref(false);
const actionError   = ref('');

// --- Computed ---
const skillSlots = computed(() => character.value?.skill_slots ?? 1);

const equippedSkills   = computed(() => skills.value.filter(s => s.equipped));
const unequippedSkills = computed(() => skills.value.filter(s => !s.equipped));

function skillInSlot(slotIndex) {
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
    const KEYS   = ['hp','mp','sp','attack','defense','movement','jump_height','crit_chance','crit_damage'];
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
        const [charData, skillsData, equipData] = await Promise.all([
            auth.get(`/profile/character/${props.characterId}`),
            skillService.listCharacterSkills(props.characterId),
            inventoryService.getEquipment(props.characterId),
        ]);
        character.value = charData;
        skills.value    = Array.isArray(skillsData) ? skillsData : [];
        equipment.value = {
            armor:   equipData?.armor   ?? null,
            weapon:  equipData?.weapon  ?? null,
            utility: equipData?.utility ?? null,
        };
    } catch (e) {
        error.value = e?.message ?? 'Unknown fault.';
    } finally {
        loading.value = false;
    }
}

watch(() => props.characterId, (id) => { if (id) load(); });

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
        await Promise.all([load(), refreshState()]);
    } catch (e) {
        actionError.value = e?.message ?? 'Link failed.';
    } finally { actionLoading.value = false; }
}

async function unequipSlot(slot) {
    actionLoading.value = true; actionError.value = '';
    try {
        await inventoryService.unequip(props.characterId, slot);
        await Promise.all([load(), refreshState()]);
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
        actionError.value = e?.message ?? 'Protocol link failed.';
    } finally { actionLoading.value = false; }
}

async function unequipSkill(skill) {
    actionLoading.value = true; actionError.value = '';
    try {
        await skillService.unequip(props.characterId, skill.id);
        await load();
    } catch (e) {
        actionError.value = e?.message ?? 'Protocol sever failed.';
    } finally { actionLoading.value = false; }
}

const slotNames = ['armor', 'weapon', 'utility'];
const slotIcons = { armor: '◈', weapon: '⚔', utility: '◉' };
</script>

<template>
    <Teleport to="body">
        <!-- Panel -->
        <div
            data-testid="diagnostic-terminal"
            class="fixed inset-y-0 right-0 z-40 w-[440px] flex flex-col
                   bg-upsilon-gunmetal/90 backdrop-blur-xl
                   border-l border-upsilon-cyan/30
                   border-t-2 border-t-upsilon-cyan/60"
            :class="characterId ? 'translate-x-0' : 'translate-x-full'"
            style="transition: transform 280ms linear;"
        >
            <!-- HUD corner accent (top-left) -->
            <div class="absolute top-0 left-0 w-4 h-4 border-t-2 border-l-2 border-upsilon-cyan/60 pointer-events-none"></div>

            <!-- Header -->
            <div class="flex items-center justify-between px-4 py-3 border-b border-upsilon-cyan/20 shrink-0">
                <span class="font-scifi text-xs uppercase tracking-[0.3em] text-upsilon-cyan">
                    ◈ DIAGNOSTIC LINK — {{ character?.name ?? '…' }}
                </span>
                <button
                    @click="$emit('close')"
                    class="font-mono text-[10px] text-upsilon-magenta hover:text-white border border-upsilon-magenta/30 hover:border-upsilon-magenta px-2 py-0.5"
                    style="transition: color 150ms linear, border-color 150ms linear;"
                >
                    SEVER ✕
                </button>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex-1 flex items-center justify-center">
                <span class="font-mono text-[10px] text-upsilon-cyan uppercase tracking-widest animate-pulse">
                    ESTABLISHING TACTICAL LINK…
                </span>
            </div>

            <!-- Error -->
            <div v-else-if="error" class="flex-1 flex items-center justify-center p-6">
                <div class="border border-upsilon-magenta/40 px-4 py-3 w-full">
                    <div class="font-mono text-[9px] text-upsilon-magenta uppercase tracking-widest">
                        LINK CORRUPTED — {{ error }}
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div v-else-if="character" class="flex flex-1 overflow-hidden">

                <!-- LEFT: Stats + loadout slots -->
                <div class="w-52 shrink-0 flex flex-col gap-3 overflow-y-auto p-4 border-r border-upsilon-cyan/10">

                    <!-- Stats grid -->
                    <div>
                        <div class="text-[8px] font-mono text-upsilon-cyan uppercase tracking-[0.4em] mb-2">BIOMETRIC DATA</div>
                        <div class="grid grid-cols-3 gap-1">
                            <div v-for="stat in stats" :key="stat.key"
                                class="flex flex-col items-center py-1.5 bg-black/40 border border-upsilon-cyan/10">
                                <span class="font-mono text-[7px] text-upsilon-cyan/60 uppercase">{{ stat.label }}</span>
                                <span class="font-scifi text-[12px] text-white font-bold">{{ stat.value }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-upsilon-cyan/10"></div>

                    <!-- Equipment slots -->
                    <div>
                        <div class="text-[8px] font-mono text-upsilon-cyan uppercase tracking-[0.4em] mb-2">HARDWARE LINK</div>
                        <div class="space-y-1">
                            <div
                                v-for="slot in slotNames"
                                :key="slot"
                                @click="selectEquipmentSlot(slot)"
                                class="flex items-center gap-2 p-2 border cursor-pointer"
                                :class="selectedType === 'equipment' && selectedSlot === slot
                                    ? 'bg-upsilon-cyan/10 border-upsilon-cyan/60'
                                    : 'bg-black/30 border-upsilon-cyan/10 hover:border-upsilon-cyan/40'"
                                style="transition: border-color 120ms linear, background-color 120ms linear;"
                            >
                                <span class="text-xs text-upsilon-cyan/60">{{ slotIcons[slot] }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="font-mono text-[7px] text-upsilon-cyan/40 uppercase">{{ slot }}</div>
                                    <div class="font-scifi text-[9px] uppercase truncate"
                                        :class="equipment[slot] ? 'text-white' : 'text-upsilon-magenta/40'">
                                        {{ equipment[slot]?.shop_item?.name ?? 'EMPTY' }}
                                    </div>
                                </div>
                                <span class="text-upsilon-cyan/30 text-xs">›</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-upsilon-cyan/10"></div>

                    <!-- Skill slots -->
                    <div>
                        <div class="flex items-baseline justify-between mb-2">
                            <span class="text-[8px] font-mono text-upsilon-cyan uppercase tracking-[0.4em]">SKILL LOADOUT</span>
                            <span class="font-mono text-[7px] text-upsilon-cyan/40">
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
                    <div v-if="character.roulette_available" class="mt-auto pt-2 border-t border-upsilon-cyan/10">
                        <button
                            @click="$emit('roulette-click', { id: character.id, name: character.name })"
                            class="w-full py-2.5 border border-upsilon-magenta text-upsilon-magenta font-scifi text-[9px] uppercase tracking-[0.3em] hover:bg-upsilon-magenta/10 animate-pulse"
                            style="box-shadow: 0 0 8px rgba(255,0,255,0.3); transition: background-color 150ms linear;"
                        >
                            ◈ SCAVENGE SKILL ◈
                        </button>
                        <div class="font-mono text-[7px] text-upsilon-magenta/60 text-center mt-1 uppercase tracking-widest">
                            One-time acquisition available
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Swap UI -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4">

                    <!-- No selection -->
                    <div v-if="!selectedType" class="h-full flex items-center justify-center">
                        <p class="font-mono text-[10px] text-upsilon-cyan/40 uppercase tracking-widest text-center">
                            SELECT A SLOT<br/>TO MANAGE LOADOUT
                        </p>
                    </div>

                    <!-- Action error -->
                    <div v-if="actionError" class="px-3 py-2 border border-upsilon-magenta/50 bg-upsilon-magenta/10 font-mono text-[9px] text-upsilon-magenta uppercase">
                        ⚠ {{ actionError }}
                    </div>

                    <!-- EQUIPMENT SLOT UI -->
                    <div v-if="selectedType === 'equipment'" class="space-y-5">
                        <div>
                            <div class="text-[8px] font-mono text-upsilon-cyan uppercase tracking-[0.4em] mb-3">
                                ACTIVE LINK — {{ selectedSlot?.toUpperCase() }}
                            </div>
                            <div v-if="currentEquipment" class="p-3 bg-black/40 border border-upsilon-cyan/10 space-y-3">
                                <div>
                                    <div class="font-scifi text-sm text-white uppercase tracking-widest">
                                        {{ currentEquipment.shop_item?.name }}
                                    </div>
                                    <div class="font-mono text-[8px] text-upsilon-lime/70 uppercase mt-1">
                                        Linked to this operative
                                    </div>
                                </div>
                                <button
                                    :disabled="actionLoading"
                                    @click="unequipSlot(selectedSlot)"
                                    class="px-4 py-1.5 border border-upsilon-magenta/40 text-upsilon-magenta font-scifi text-[9px] uppercase hover:bg-upsilon-magenta/10 disabled:opacity-40"
                                    style="transition: background-color 120ms linear;"
                                >
                                    Terminate Link
                                </button>
                            </div>
                            <div v-else class="p-3 border border-dashed border-upsilon-cyan/10 font-mono text-[9px] text-upsilon-magenta/40 uppercase">
                                No hardware linked to this slot.
                            </div>
                        </div>

                        <div>
                            <div class="text-[8px] font-mono text-upsilon-cyan uppercase tracking-[0.4em] mb-3">
                                COMPATIBLE MANIFESTS
                            </div>
                            <div v-if="!compatibleInventory.length" class="py-5 text-center border border-dashed border-upsilon-cyan/10">
                                <span class="font-mono text-[9px] text-upsilon-magenta/40 uppercase">No compatible hardware in inventory.</span>
                            </div>
                            <div v-else class="space-y-2">
                                <div
                                    v-for="item in compatibleInventory"
                                    :key="item.id"
                                    class="flex items-center justify-between p-3 bg-black/40 border border-upsilon-cyan/10 hover:border-upsilon-cyan/40"
                                    style="transition: border-color 120ms linear;"
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
                                        class="px-3 py-1 border border-upsilon-cyan/40 text-upsilon-cyan font-scifi text-[8px] uppercase hover:bg-upsilon-cyan/10 disabled:opacity-40"
                                        style="transition: background-color 120ms linear;"
                                    >
                                        Link
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SKILL SLOT UI -->
                    <div v-else-if="selectedType === 'skill'" class="space-y-5">
                        <div>
                            <div class="text-[8px] font-mono text-upsilon-cyan uppercase tracking-[0.4em] mb-3">
                                SLOT {{ selectedSlot + 1 }} — CURRENT PROTOCOL
                            </div>
                            <div v-if="currentSkillInSelectedSlot" class="space-y-3">
                                <SkillDetail :skill="currentSkillInSelectedSlot" />
                                <button
                                    :disabled="actionLoading"
                                    @click="unequipSkill(currentSkillInSelectedSlot)"
                                    class="px-4 py-1.5 border border-upsilon-magenta/40 text-upsilon-magenta font-scifi text-[9px] uppercase hover:bg-upsilon-magenta/10 disabled:opacity-40"
                                    style="transition: background-color 120ms linear;"
                                >
                                    Remove Protocol
                                </button>
                            </div>
                            <div v-else class="p-3 border border-dashed border-upsilon-cyan/10 font-mono text-[9px] text-upsilon-magenta/40 uppercase">
                                Empty slot — no protocol active.
                            </div>
                        </div>

                        <div v-if="unequippedSkills.length">
                            <div class="text-[8px] font-mono text-upsilon-cyan uppercase tracking-[0.4em] mb-3">
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
                                        class="shrink-0 px-3 py-1.5 border border-upsilon-cyan/40 text-upsilon-cyan font-scifi text-[8px] uppercase hover:bg-upsilon-cyan/10 disabled:opacity-40"
                                        style="transition: background-color 120ms linear;"
                                    >
                                        Equip
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="!currentSkillInSelectedSlot" class="py-4 text-center border border-dashed border-upsilon-cyan/10">
                            <span class="font-mono text-[9px] text-upsilon-magenta/40 uppercase">No spare protocols. Use SCAVENGE SKILL to acquire one.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backdrop -->
        <Transition name="dt-backdrop">
            <div
                v-if="characterId"
                class="fixed inset-0 z-30 bg-black/40 backdrop-blur-sm"
                @click="$emit('close')"
            ></div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.dt-backdrop-enter-active,
.dt-backdrop-leave-active {
    transition: opacity 200ms linear;
}
.dt-backdrop-enter-from,
.dt-backdrop-leave-to {
    opacity: 0;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
.animate-pulse {
    animation: pulse 2s linear infinite;
}
</style>
