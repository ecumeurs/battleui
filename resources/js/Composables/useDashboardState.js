// @spec-link [[ui_dashboard]]
import { ref } from 'vue';
import auth from '@/services/auth';
import inventoryService from '@/services/inventory';
import skillService from '@/services/skill';

// ---------------------------------------------------------------------------
// Module-level singleton refs — shared across all component instances.
// ---------------------------------------------------------------------------
const characters       = ref([]);
const inventory        = ref([]);
const user             = ref(null);
const loading          = ref(true);
const initialized      = ref(false);

// Per-character detail cache: { [characterId]: { character, skills, equipment, loading } }
const characterDetails = ref({});

function reset() {
    user.value             = null;
    characters.value       = [];
    inventory.value        = [];
    characterDetails.value = {};
    initialized.value      = false;
    loading.value          = true;
}

export function useDashboardState() {

    // -----------------------------------------------------------------------
    // Dashboard initialisation (called once on mount)
    // -----------------------------------------------------------------------
    async function init(authUser) {
        // Reset when a different user logs in within the same SPA session.
        if (user.value && user.value.id !== authUser?.id) reset();
        if (initialized.value) return;
        user.value   = authUser;
        loading.value = true;
        try {
            const [chars, inv] = await Promise.all([
                auth.get('/profile/characters'),
                inventoryService.listInventory(),
            ]);
            characters.value = Array.isArray(chars) ? chars : [];
            inventory.value  = Array.isArray(inv)   ? inv   : [];
            initialized.value = true;
        } finally {
            loading.value = false;
        }
    }

    // -----------------------------------------------------------------------
    // Full roster + inventory refresh (after equip/purchase etc.)
    // -----------------------------------------------------------------------
    async function refresh() {
        loading.value = true;
        try {
            const [chars, inv] = await Promise.all([
                auth.get('/profile/characters'),
                inventoryService.listInventory(),
            ]);
            characters.value = Array.isArray(chars) ? chars : [];
            inventory.value  = Array.isArray(inv)   ? inv   : [];
        } finally {
            loading.value = false;
        }
    }

    // -----------------------------------------------------------------------
    // Shallow-merge a character update into the roster list
    // -----------------------------------------------------------------------
    function updateCharacter(updated) {
        const i = characters.value.findIndex(c => c.id === updated.id);
        if (i !== -1) characters.value[i] = { ...characters.value[i], ...updated };
    }

    function updateUser(patch) {
        user.value = { ...user.value, ...patch };
    }

    // -----------------------------------------------------------------------
    // Per-character detail cache
    // -----------------------------------------------------------------------

    async function loadCharacterDetails(id) {
        if (!id) return;
        if (characterDetails.value[id]?.loading) return;

        characterDetails.value = {
            ...characterDetails.value,
            [id]: { ...(characterDetails.value[id] ?? {}), loading: true, error: null },
        };

        try {
            const [charData, skillsData, equipData] = await Promise.all([
                auth.get(`/profile/character/${id}`),
                skillService.listCharacterSkills(id),
                inventoryService.getEquipment(id),
            ]);
            characterDetails.value = {
                ...characterDetails.value,
                [id]: {
                    loading:   false,
                    error:     null,
                    character: charData,
                    skills:    Array.isArray(skillsData) ? skillsData : [],
                    equipment: {
                        armor:   equipData?.armor   ?? null,
                        weapon:  equipData?.weapon  ?? null,
                        utility: equipData?.utility ?? null,
                    },
                },
            };
        } catch (e) {
            characterDetails.value = {
                ...characterDetails.value,
                [id]: { ...(characterDetails.value[id] ?? {}), loading: false, error: e?.message ?? 'Load failed.' },
            };
        }
    }

    function patchCharacterDetail(id, patch) {
        const entry = characterDetails.value[id];
        if (!entry) return;
        characterDetails.value = {
            ...characterDetails.value,
            [id]: { ...entry, character: { ...entry.character, ...patch } },
        };
        updateCharacter({ id, ...patch });
    }

    function addSkillToCharacter(id, skill) {
        const entry = characterDetails.value[id];
        if (!entry) return;
        characterDetails.value = {
            ...characterDetails.value,
            [id]: { ...entry, skills: [...entry.skills, skill] },
        };
    }

    function setCharacterEquipmentSlot(id, slot, item) {
        const entry = characterDetails.value[id];
        if (!entry) return;
        characterDetails.value = {
            ...characterDetails.value,
            [id]: { ...entry, equipment: { ...entry.equipment, [slot]: item } },
        };
    }

    function clearCharacterDetails(id) {
        const next = { ...characterDetails.value };
        delete next[id];
        characterDetails.value = next;
    }

    return {
        characters,
        inventory,
        user,
        loading,
        initialized,
        characterDetails,
        init,
        reset,
        refresh,
        updateCharacter,
        updateUser,
        loadCharacterDetails,
        patchCharacterDetail,
        addSkillToCharacter,
        setCharacterEquipmentSlot,
        clearCharacterDetails,
    };
}
