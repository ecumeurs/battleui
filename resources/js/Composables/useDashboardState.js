// @spec-link [[ui_dashboard]]
import { ref } from 'vue';
import auth from '@/services/auth';
import inventoryService from '@/services/inventory';

// Module-level singleton refs — shared across all component instances that call this composable.
const characters  = ref([]);
const inventory   = ref([]);
const user        = ref(null);
const loading     = ref(true);  // true until first init() completes, so waitForRoster() works correctly
const initialized = ref(false);

export function useDashboardState() {
    async function init(authUser) {
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

    function updateCharacter(updated) {
        const i = characters.value.findIndex(c => c.id === updated.id);
        if (i !== -1) characters.value[i] = { ...characters.value[i], ...updated };
    }

    function updateUser(patch) {
        user.value = { ...user.value, ...patch };
    }

    return { characters, inventory, user, loading, initialized, init, refresh, updateCharacter, updateUser };
}
