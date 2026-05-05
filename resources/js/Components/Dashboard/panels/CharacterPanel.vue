<!-- Character slide-out panel: stats, equipment, skills, reroll, roulette.
     Replaces DiagnosticTerminal. @spec-link [[ui_diagnostic_terminal]] -->
<script setup>
import { ref, computed, watch } from 'vue';
import { useDashboardState } from '@/Composables/useDashboardState';
import SkillDetail from '@/Components/Skill/SkillDetail.vue';
import SkillSlotPill from '@/Components/Skill/SkillSlotPill.vue';
import SkillCard from '@/Components/Skill/SkillCard.vue';
import SkillRouletteReel from '@/Components/Skill/SkillRouletteReel.vue';
import inventoryService from '@/services/inventory';
import skillService from '@/services/skill';
import auth from '@/services/auth';

const props = defineProps({
    characterId: { type: String, default: null },
    user:        { type: Object, default: null },
});

const emit = defineEmits(['close']);

const {
    inventory,
    refresh: refreshState,
    characterDetails,
    loadCharacterDetails,
    patchCharacterDetail,
    addSkillToCharacter,
} = useDashboardState();

// Derived from shared state
const detailEntry  = computed(() => characterDetails.value[props.characterId] ?? null);
const character    = computed(() => detailEntry.value?.character ?? null);
const skills       = computed(() => detailEntry.value?.skills ?? []);
const equipment    = computed(() => detailEntry.value?.equipment ?? { armor: null, weapon: null, utility: null });
const loading      = computed(() => detailEntry.value?.loading ?? true);
const loadError    = computed(() => detailEntry.value?.error ?? null);

// Slot selection state
const selectedType = ref(null);   // 'equipment' | 'skill'
const selectedSlot = ref(null);

// Sub-view: null | 'reroll-confirm' | 'roulette' | 'rename'
const subView      = ref(null);
const actionLoading = ref(false);
const actionError   = ref('');

// Rename
const nameInput    = ref('');

// Roulette
const rouletteFsm  = ref('idle');  // idle | spinning | revealing | revealed
const rouletteErr  = ref('');
const rolledSkill  = ref(null);
const winnerReel   = ref(0);
const templateNames = ref([]);
const reel0 = ref(null);
const reel1 = ref(null);
const reel2 = ref(null);
const reels = computed(() => [reel0.value, reel1.value, reel2.value]);

// Computed
const skillSlots   = computed(() => character.value?.skill_slots ?? 1);
const equippedSkills   = computed(() => skills.value.filter(s => s.equipped));
const unequippedSkills = computed(() => skills.value.filter(s => !s.equipped));
const skillInSlot  = (i) => equippedSkills.value[i] ?? null;

const currentEquipment = computed(() => {
    if (selectedType.value !== 'equipment' || !selectedSlot.value) return null;
    return equipment.value[selectedSlot.value] ?? null;
});

const compatibleInventory = computed(() => {
    if (selectedType.value !== 'equipment' || !selectedSlot.value) return [];
    return inventory.value.filter(i => i.shop_item?.slot === selectedSlot.value);
});

const currentSkillInSlot = computed(() => {
    if (selectedType.value !== 'skill' || selectedSlot.value === null) return null;
    return skillInSlot(selectedSlot.value);
});

const stats = computed(() => {
    if (!character.value) return [];
    const KEYS   = ['hp','mp','sp','attack','defense','movement','jump_height','crit_chance','crit_damage'];
    const labels = { hp:'HP', mp:'MP', sp:'SP', attack:'ATK', defense:'DEF', movement:'MOV',
                     jump_height:'JMP', crit_chance:'CRT%', crit_damage:'CRT+' };
    return KEYS.map(k => ({ key: k, label: labels[k], value: character.value[k] ?? 0 }));
});

watch(() => props.characterId, (id) => {
    selectedType.value = null;
    selectedSlot.value = null;
    subView.value = null;
    actionError.value = '';
    if (id) loadCharacterDetails(id);
}, { immediate: true });

// Slot selection
function selectEquipmentSlot(slot) {
    if (subView.value) { subView.value = null; }
    actionError.value = '';
    if (selectedType.value === 'equipment' && selectedSlot.value === slot) {
        selectedType.value = null; selectedSlot.value = null;
    } else {
        selectedType.value = 'equipment'; selectedSlot.value = slot;
    }
}

function selectSkillSlot(index) {
    if (subView.value) { subView.value = null; }
    actionError.value = '';
    if (selectedType.value === 'skill' && selectedSlot.value === index) {
        selectedType.value = null; selectedSlot.value = null;
    } else {
        selectedType.value = 'skill'; selectedSlot.value = index;
    }
}

// Equipment actions
async function equipItem(item) {
    actionLoading.value = true; actionError.value = '';
    try {
        await inventoryService.equip(props.characterId, item.id);
        await Promise.all([loadCharacterDetails(props.characterId), refreshState()]);
    } catch (e) {
        actionError.value = e?.message ?? 'Link failed.';
    } finally { actionLoading.value = false; }
}

async function unequipSlot(slot) {
    actionLoading.value = true; actionError.value = '';
    try {
        await inventoryService.unequip(props.characterId, slot);
        await Promise.all([loadCharacterDetails(props.characterId), refreshState()]);
    } catch (e) {
        actionError.value = e?.message ?? 'Sever failed.';
    } finally { actionLoading.value = false; }
}

// Skill actions
async function equipSkill(skill) {
    actionLoading.value = true; actionError.value = '';
    try {
        await skillService.equip(props.characterId, skill.id);
        await loadCharacterDetails(props.characterId);
    } catch (e) {
        actionError.value = e?.message ?? 'Protocol link failed.';
    } finally { actionLoading.value = false; }
}

async function unequipSkill(skill) {
    actionLoading.value = true; actionError.value = '';
    try {
        await skillService.unequip(props.characterId, skill.id);
        await loadCharacterDetails(props.characterId);
    } catch (e) {
        actionError.value = e?.message ?? 'Protocol sever failed.';
    } finally { actionLoading.value = false; }
}

// Rename
function openRename() {
    nameInput.value = character.value?.name ?? '';
    subView.value = 'rename';
    selectedType.value = null;
    selectedSlot.value = null;
    actionError.value = '';
}

async function submitRename() {
    if (nameInput.value.trim().length < 3) return;
    actionLoading.value = true;
    try {
        const data = await auth.post(`/profile/character/${props.characterId}/rename`, { name: nameInput.value.trim() });
        patchCharacterDetail(props.characterId, data);
        subView.value = null;
    } catch (e) {
        actionError.value = e?.message ?? 'Rename failed.';
    } finally { actionLoading.value = false; }
}

// Reroll
async function confirmReroll() {
    actionLoading.value = true; actionError.value = '';
    try {
        const data = await auth.post(`/profile/character/${props.characterId}/reroll`);
        patchCharacterDetail(props.characterId, data.character ?? data);
        subView.value = null;
    } catch (e) {
        actionError.value = e?.message ?? 'Reroll failed.';
    } finally { actionLoading.value = false; }
}

// Roulette
async function openRoulette() {
    subView.value = 'roulette';
    selectedType.value = null;
    selectedSlot.value = null;
    rouletteFsm.value = 'idle';
    rouletteErr.value = '';
    rolledSkill.value = null;
    winnerReel.value = Math.floor(Math.random() * 3);
    try {
        const result = await skillService.listTemplates();
        templateNames.value = Array.isArray(result)
            ? result.map(t => t.name)
            : ['Fireball', 'Heal', 'Sprint', 'Lightning Strike', 'Shield Bash', 'Regen Aura'];
    } catch {
        templateNames.value = ['Fireball', 'Heal', 'Sprint', 'Lightning Strike', 'Shield Bash', 'Regen Aura'];
    }
}

function spin() {
    if (rouletteFsm.value !== 'idle') return;
    rouletteFsm.value = 'spinning';
    reels.value.forEach(r => r?.start(templateNames.value));
}

async function stopRoulette() {
    if (rouletteFsm.value !== 'spinning') return;
    rouletteFsm.value = 'revealing';
    rouletteErr.value = '';
    let skill = null;
    try {
        skill = await skillService.roll(props.characterId);
    } catch (e) {
        rouletteErr.value = e?.message ?? 'Acquisition failed. Generator offline.';
        rouletteFsm.value = 'idle';
        reels.value.forEach(r => r?.reset());
        return;
    }
    rolledSkill.value = skill;
    const rolledName = skill.instance_data?.name ?? skill.name ?? 'Unknown';
    const decoys = templateNames.value.filter(n => n !== rolledName);
    reels.value.forEach((reel, i) => {
        setTimeout(() => {
            const target = i === winnerReel.value
                ? rolledName
                : decoys[i % decoys.length] || 'Unknown';
            reel?.stop(target);
            if (i === 2) setTimeout(() => { rouletteFsm.value = 'revealed'; }, 400);
        }, i * 500);
    });
}

function acceptSkill() {
    addSkillToCharacter(props.characterId, rolledSkill.value);
    patchCharacterDetail(props.characterId, { roulette_available: false });
    subView.value = null;
}

const slotNames = ['armor', 'weapon', 'utility'];
const slotIcons = { armor: '◈', weapon: '⚔', utility: '◉' };
</script>

<template>
    <div data-testid="diagnostic-terminal" class="flex flex-col flex-1 overflow-hidden">
        <!-- Corner accent -->
        <div class="absolute top-0 left-0 w-4 h-4 border-t-2 border-l-2 border-upsilon-cyan/60 pointer-events-none"></div>

        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-upsilon-cyan/20 shrink-0">
            <button
                @click="openRename"
                class="font-scifi text-ui-sm uppercase tracking-[0.3em] text-upsilon-cyan hover:text-white"
                style="transition: color 150ms linear;"
                :title="character ? 'Click to rename' : ''"
            >
                ◈ OPERATIVE — {{ character?.name ?? '…' }}
            </button>
            <button
                @click="$emit('close')"
                class="font-mono text-ui-sm text-upsilon-magenta hover:text-white border border-upsilon-magenta/30 hover:border-upsilon-magenta px-2 py-0.5"
                style="transition: color 150ms linear, border-color 150ms linear;"
            >
                SEVER ✕
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex-1 flex items-center justify-center">
            <span class="font-mono text-ui-sm text-upsilon-cyan uppercase tracking-widest animate-pulse">
                ESTABLISHING TACTICAL LINK…
            </span>
        </div>

        <!-- Error -->
        <div v-else-if="loadError" class="flex-1 flex items-center justify-center p-6">
            <div class="border border-upsilon-magenta/40 px-4 py-3 w-full">
                <div class="font-mono text-ui-xs text-upsilon-magenta uppercase tracking-widest">
                    LINK CORRUPTED — {{ loadError }}
                </div>
            </div>
        </div>

        <!-- Content -->
        <div v-else-if="character" class="flex flex-1 overflow-hidden">

            <!-- LEFT: Stats + loadout slots (220px) -->
            <div class="w-56 shrink-0 flex flex-col gap-3 overflow-y-auto p-4 border-r border-upsilon-cyan/10">

                <!-- Stat ID -->
                <div class="font-mono text-ui-xs text-upsilon-steel/50 uppercase tracking-widest">
                    SYS {{ character.id.split('-')[0] }}
                </div>

                <!-- Stats grid -->
                <div>
                    <div class="text-ui-xs font-mono text-upsilon-cyan uppercase tracking-[0.4em] mb-2">BIOMETRIC DATA</div>
                    <div class="grid grid-cols-3 gap-1">
                        <div v-for="stat in stats" :key="stat.key"
                            class="flex flex-col items-center py-1.5 bg-black/40 border border-upsilon-cyan/10">
                            <span class="font-mono text-ui-xs text-upsilon-cyan/60 uppercase">{{ stat.label }}</span>
                            <span class="font-scifi text-ui-md text-white font-bold">{{ stat.value }}</span>
                        </div>
                    </div>
                </div>

                <div class="border-t border-upsilon-cyan/10"></div>

                <!-- Equipment slots -->
                <div>
                    <div class="text-ui-xs font-mono text-upsilon-cyan uppercase tracking-[0.4em] mb-2">HARDWARE LINK</div>
                    <div class="space-y-1">
                        <div
                            v-for="slot in slotNames"
                            :key="slot"
                            @click="selectEquipmentSlot(slot)"
                            class="flex items-center gap-2 p-2 border cursor-pointer"
                            :class="selectedType === 'equipment' && selectedSlot === slot && !subView
                                ? 'bg-upsilon-cyan/10 border-upsilon-cyan/60'
                                : 'bg-black/30 border-upsilon-cyan/10 hover:border-upsilon-cyan/40'"
                            style="transition: border-color 120ms linear, background-color 120ms linear;"
                        >
                            <span class="text-ui-sm text-upsilon-cyan/60">{{ slotIcons[slot] }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="font-mono text-ui-xs text-upsilon-cyan/40 uppercase">{{ slot }}</div>
                                <div class="font-scifi text-ui-xs uppercase truncate"
                                    :class="equipment[slot] ? 'text-white' : 'text-upsilon-magenta/40'">
                                    {{ equipment[slot]?.shop_item?.name ?? 'EMPTY' }}
                                </div>
                            </div>
                            <span class="text-upsilon-cyan/30 text-ui-sm">›</span>
                        </div>
                    </div>
                </div>

                <div class="border-t border-upsilon-cyan/10"></div>

                <!-- Skill slots -->
                <div>
                    <div class="flex items-baseline justify-between mb-2">
                        <span class="text-ui-xs font-mono text-upsilon-cyan uppercase tracking-[0.4em]">SKILL LOADOUT</span>
                        <span class="font-mono text-ui-xs text-upsilon-cyan/40">
                            {{ equippedSkills.length }}/{{ skillSlots }}
                        </span>
                    </div>
                    <div class="space-y-1">
                        <SkillSlotPill
                            v-for="i in skillSlots"
                            :key="i - 1"
                            :slot="i - 1"
                            :skill="skillInSlot(i - 1)"
                            :selected="selectedType === 'skill' && selectedSlot === (i - 1) && !subView"
                            @click="selectSkillSlot(i - 1)"
                        />
                    </div>
                </div>

                <!-- Reroll (total_wins === 0 only) -->
                <div v-if="props.user?.total_wins === 0" class="border-t border-upsilon-cyan/10 pt-2">
                    <button
                        @click="subView = 'reroll-confirm'; selectedType = null;"
                        class="w-full py-2 border border-upsilon-rust/40 text-upsilon-rust font-scifi text-ui-xs uppercase tracking-[0.3em] hover:bg-upsilon-rust/10"
                        style="transition: background-color 150ms linear;"
                    >
                        ⟳ REROLL
                    </button>
                </div>

                <!-- Scavenge skill -->
                <div v-if="character.roulette_available" class="mt-auto pt-2 border-t border-upsilon-cyan/10">
                    <button
                        @click="openRoulette"
                        class="w-full py-2.5 border border-upsilon-magenta text-upsilon-magenta font-scifi text-ui-xs uppercase tracking-[0.3em] hover:bg-upsilon-magenta/10 animate-pulse"
                        style="box-shadow: 0 0 8px rgba(255,0,255,0.3); transition: background-color 150ms linear;"
                    >
                        ◈ SCAVENGE SKILL ◈
                    </button>
                    <div class="font-mono text-ui-xs text-upsilon-magenta/60 text-center mt-1 uppercase tracking-widest">
                        One-time acquisition available
                    </div>
                </div>
            </div>

            <!-- RIGHT: Contextual pane -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4">

                <!-- RENAME sub-view -->
                <div v-if="subView === 'rename'" class="space-y-4">
                    <div class="text-ui-xs font-mono text-upsilon-cyan uppercase tracking-[0.4em]">RENAME OPERATIVE</div>
                    <input
                        v-model="nameInput"
                        type="text"
                        class="w-full bg-black/40 border border-upsilon-cyan/40 text-white px-3 py-2 font-mono text-ui-md focus:outline-none focus:border-upsilon-cyan"
                        @keyup.enter="submitRename"
                        @keyup.esc="subView = null"
                        autofocus
                    />
                    <div v-if="actionError" class="font-mono text-ui-xs text-upsilon-magenta uppercase">⚠ {{ actionError }}</div>
                    <div class="flex gap-2">
                        <button
                            :disabled="actionLoading || nameInput.trim().length < 3"
                            @click="submitRename"
                            class="px-4 py-1.5 border border-upsilon-lime/40 text-upsilon-lime font-scifi text-ui-xs uppercase hover:bg-upsilon-lime/10 disabled:opacity-40"
                        >
                            {{ actionLoading ? 'SAVING…' : 'SAVE' }}
                        </button>
                        <button
                            @click="subView = null; actionError = '';"
                            class="px-4 py-1.5 border border-upsilon-steel/30 text-upsilon-steel font-scifi text-ui-xs uppercase hover:bg-upsilon-steel/10"
                        >
                            CANCEL
                        </button>
                    </div>
                </div>

                <!-- REROLL confirm sub-view -->
                <div v-else-if="subView === 'reroll-confirm'" class="space-y-5">
                    <div class="text-ui-xs font-mono text-upsilon-cyan uppercase tracking-[0.4em]">Reroll Protocol</div>
                    <div class="p-4 border border-upsilon-rust/30 bg-upsilon-rust/5 font-mono text-ui-sm text-upsilon-lime leading-relaxed">
                        Regenerating all stats to baseline. All CP upgrades will be wiped. This cannot be undone.
                    </div>
                    <div v-if="actionError" class="font-mono text-ui-xs text-upsilon-magenta uppercase">⚠ {{ actionError }}</div>
                    <div class="flex gap-3">
                        <button
                            :disabled="actionLoading"
                            @click="confirmReroll"
                            class="px-6 py-2 border border-upsilon-rust text-upsilon-rust font-scifi text-ui-xs uppercase hover:bg-upsilon-rust/10 disabled:opacity-40"
                            style="transition: background-color 150ms linear;"
                        >
                            {{ actionLoading ? 'REGENERATING…' : 'Reroll' }}
                        </button>
                        <button
                            :disabled="actionLoading"
                            @click="subView = null; actionError = '';"
                            class="px-6 py-2 border border-upsilon-steel/30 text-upsilon-steel font-scifi text-ui-xs uppercase hover:bg-upsilon-steel/10 disabled:opacity-40"
                        >
                            Abort
                        </button>
                    </div>
                </div>

                <!-- ROULETTE sub-view -->
                <div v-else-if="subView === 'roulette'" class="space-y-6">
                    <div class="text-ui-xs font-mono text-upsilon-cyan uppercase tracking-[0.4em]">Randomized Skill Acquisition</div>
                    <div class="font-mono text-ui-xs text-upsilon-steel/60 uppercase tracking-widest">Roulette protocol — one-time character event</div>

                    <!-- Reels -->
                    <div class="flex items-center justify-center gap-4">
                        <SkillRouletteReel ref="reel0" :names="templateNames" :is-winner="winnerReel === 0 && rouletteFsm === 'revealed'" />
                        <SkillRouletteReel ref="reel1" :names="templateNames" :is-winner="winnerReel === 1 && rouletteFsm === 'revealed'" />
                        <SkillRouletteReel ref="reel2" :names="templateNames" :is-winner="winnerReel === 2 && rouletteFsm === 'revealed'" />
                    </div>

                    <!-- Error -->
                    <div v-if="rouletteErr" class="px-4 py-3 border border-upsilon-magenta/50 bg-upsilon-magenta/10 font-mono text-ui-xs text-upsilon-magenta uppercase text-center">
                        ⚠ {{ rouletteErr }}
                    </div>

                    <!-- Controls -->
                    <div class="flex justify-center gap-4">
                        <button
                            v-if="rouletteFsm === 'idle'"
                            @click="spin"
                            class="px-8 py-3 font-scifi text-ui-sm uppercase tracking-[0.3em] border border-upsilon-cyan text-upsilon-cyan hover:bg-upsilon-cyan hover:text-black"
                            style="box-shadow: 0 0 8px rgba(0,242,255,0.3); transition: all 300ms;"
                        >
                            ◈ INITIATE SPIN
                        </button>
                        <button
                            v-if="rouletteFsm === 'spinning'"
                            @click="stopRoulette"
                            class="px-8 py-3 font-scifi text-ui-sm uppercase tracking-[0.3em] border border-upsilon-magenta text-upsilon-magenta hover:bg-upsilon-magenta hover:text-white animate-pulse"
                            style="box-shadow: 0 0 8px rgba(255,0,255,0.4); transition: all 300ms;"
                        >
                            ⬡ STOP
                        </button>
                        <div v-if="rouletteFsm === 'revealing'" class="font-mono text-ui-xs text-upsilon-steel uppercase tracking-widest animate-pulse py-3">
                            Resolving acquisition...
                        </div>
                    </div>

                    <!-- Revealed -->
                    <div v-if="rouletteFsm === 'revealed' && rolledSkill" class="border border-upsilon-magenta/30 bg-black/40 p-5 space-y-5">
                        <div class="text-center">
                            <div class="font-mono text-ui-xs text-upsilon-magenta uppercase tracking-[0.5em] mb-1 animate-pulse">
                                ◈ SKILL ACQUIRED ◈
                            </div>
                            <div class="font-mono text-ui-xs text-upsilon-steel uppercase tracking-widest">
                                Protocol registered to this operative.
                            </div>
                        </div>
                        <SkillDetail :skill="rolledSkill" />
                        <div class="flex justify-center">
                            <button
                                @click="acceptSkill"
                                class="px-8 py-3 font-scifi text-ui-sm uppercase tracking-[0.3em] bg-upsilon-magenta/20 border border-upsilon-magenta text-upsilon-magenta hover:bg-upsilon-magenta hover:text-white"
                                style="box-shadow: 0 0 12px rgba(255,0,255,0.4); transition: all 300ms;"
                            >
                                ⬢ ACCEPT & CLOSE
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            v-if="rouletteFsm !== 'spinning'"
                            @click="subView = null"
                            class="font-mono text-ui-xs text-upsilon-steel/60 uppercase hover:text-upsilon-steel"
                        >
                            ← BACK TO LOADOUT
                        </button>
                    </div>
                </div>

                <!-- Default: equipment/skill swap UI -->
                <template v-else>
                    <!-- No selection placeholder -->
                    <div v-if="!selectedType" class="h-full flex items-center justify-center">
                        <p class="font-mono text-ui-sm text-upsilon-cyan/40 uppercase tracking-widest text-center">
                            SELECT A SLOT<br/>TO MANAGE LOADOUT
                        </p>
                    </div>

                    <!-- Action error -->
                    <div v-if="actionError" class="px-3 py-2 border border-upsilon-magenta/50 bg-upsilon-magenta/10 font-mono text-ui-xs text-upsilon-magenta uppercase">
                        ⚠ {{ actionError }}
                    </div>

                    <!-- EQUIPMENT slot UI -->
                    <div v-if="selectedType === 'equipment'" class="space-y-5">
                        <div>
                            <div class="text-ui-xs font-mono text-upsilon-cyan uppercase tracking-[0.4em] mb-3">
                                ACTIVE LINK — {{ selectedSlot?.toUpperCase() }}
                            </div>
                            <div v-if="currentEquipment" class="p-3 bg-black/40 border border-upsilon-cyan/10 space-y-3">
                                <div>
                                    <div class="font-scifi text-ui-md text-white uppercase tracking-widest">
                                        {{ currentEquipment.shop_item?.name }}
                                    </div>
                                    <div class="font-mono text-ui-xs text-upsilon-lime/70 uppercase mt-1">Linked to this operative</div>
                                </div>
                                <button
                                    :disabled="actionLoading"
                                    @click="unequipSlot(selectedSlot)"
                                    class="px-4 py-1.5 border border-upsilon-magenta/40 text-upsilon-magenta font-scifi text-ui-xs uppercase hover:bg-upsilon-magenta/10 disabled:opacity-40"
                                    style="transition: background-color 120ms linear;"
                                >
                                    Terminate Link
                                </button>
                            </div>
                            <div v-else class="p-3 border border-dashed border-upsilon-cyan/10 font-mono text-ui-xs text-upsilon-magenta/40 uppercase">
                                No hardware linked to this slot.
                            </div>
                        </div>

                        <div>
                            <div class="text-ui-xs font-mono text-upsilon-cyan uppercase tracking-[0.4em] mb-3">COMPATIBLE MANIFESTS</div>
                            <div v-if="!compatibleInventory.length" class="py-5 text-center border border-dashed border-upsilon-cyan/10">
                                <span class="font-mono text-ui-xs text-upsilon-magenta/40 uppercase">No compatible hardware in inventory.</span>
                            </div>
                            <div v-else class="space-y-2">
                                <div
                                    v-for="item in compatibleInventory"
                                    :key="item.id"
                                    class="flex items-center justify-between p-3 bg-black/40 border border-upsilon-cyan/10 hover:border-upsilon-cyan/40"
                                    style="transition: border-color 120ms linear;"
                                >
                                    <div>
                                        <div class="font-scifi text-ui-sm text-white uppercase tracking-widest">
                                            {{ item.shop_item?.name }}
                                        </div>
                                        <div class="font-mono text-ui-xs mt-0.5"
                                            :class="item.equipped_on ? 'text-upsilon-magenta' : 'text-upsilon-lime'">
                                            {{ item.equipped_on ? 'Linked to ' + item.equipped_on.name : 'Available' }}
                                        </div>
                                    </div>
                                    <button
                                        :disabled="actionLoading"
                                        @click="equipItem(item)"
                                        class="px-3 py-1 border border-upsilon-cyan/40 text-upsilon-cyan font-scifi text-ui-xs uppercase hover:bg-upsilon-cyan/10 disabled:opacity-40"
                                        style="transition: background-color 120ms linear;"
                                    >
                                        Link
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SKILL slot UI -->
                    <div v-else-if="selectedType === 'skill'" class="space-y-5">
                        <div>
                            <div class="text-ui-xs font-mono text-upsilon-cyan uppercase tracking-[0.4em] mb-3">
                                SLOT {{ selectedSlot + 1 }} — CURRENT PROTOCOL
                            </div>
                            <div v-if="currentSkillInSlot" class="space-y-3">
                                <SkillDetail :skill="currentSkillInSlot" />
                                <button
                                    :disabled="actionLoading"
                                    @click="unequipSkill(currentSkillInSlot)"
                                    class="px-4 py-1.5 border border-upsilon-magenta/40 text-upsilon-magenta font-scifi text-ui-xs uppercase hover:bg-upsilon-magenta/10 disabled:opacity-40"
                                    style="transition: background-color 120ms linear;"
                                >
                                    Remove Protocol
                                </button>
                            </div>
                            <div v-else class="p-3 border border-dashed border-upsilon-cyan/10 font-mono text-ui-xs text-upsilon-magenta/40 uppercase">
                                Empty slot — no protocol active.
                            </div>
                        </div>

                        <div v-if="unequippedSkills.length">
                            <div class="text-ui-xs font-mono text-upsilon-cyan uppercase tracking-[0.4em] mb-3">AVAILABLE PROTOCOLS</div>
                            <div class="space-y-1">
                                <div v-for="sk in unequippedSkills" :key="sk.id" class="flex items-center gap-2">
                                    <div class="flex-1"><SkillCard :skill="sk" :equipped="false" /></div>
                                    <button
                                        :disabled="actionLoading || equippedSkills.length >= skillSlots"
                                        @click="equipSkill(sk)"
                                        class="shrink-0 px-3 py-1.5 border border-upsilon-cyan/40 text-upsilon-cyan font-scifi text-ui-xs uppercase hover:bg-upsilon-cyan/10 disabled:opacity-40"
                                        style="transition: background-color 120ms linear;"
                                    >
                                        Equip
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="!currentSkillInSlot" class="py-4 text-center border border-dashed border-upsilon-cyan/10">
                            <span class="font-mono text-ui-xs text-upsilon-magenta/40 uppercase">No spare protocols. Use SCAVENGE SKILL to acquire one.</span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
.animate-pulse {
    animation: pulse 2s linear infinite;
}
</style>
