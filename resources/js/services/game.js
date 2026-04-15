// @spec-link [[ui_battle_arena]]
// @spec-link [[api_websocket_game_events]]
import auth from './auth';

export const game = {
    /**
     * Fetch the full game state.
     * @param {string} matchId 
     * @returns {Promise<Object>}
     */
    async fetchGameState(matchId) {
        const response = await auth.get(`/game/${matchId}`);
        return response; // We get data directly thanks to auth interceptor
    },

    /**
     * Send an action to the engine.
     * @param {string} matchId 
     * @param {string} playerId 
     * @param {string} entityId 
     * @param {string} type 'move' | 'attack' | 'pass' | 'forfeit'
     * @param {Array<{x: number, y: number}>} targetCoords 
     * @returns {Promise<Object>}
     */
    async sendAction(matchId, entityId, type, targetCoords = []) {
        const payload = {
            entity_id: entityId,
            type,
        };
        
        if (targetCoords && targetCoords.length > 0) {
            payload.target_coords = targetCoords;
        }

        const response = await auth.post(`/game/${matchId}/action`, payload);
        return response;
    },

    /**
     * @spec-link [[rule_forfeit_battle]]
     * @param {string} matchId 
     * @returns {Promise<Object>}
     */
    async forfeit(matchId) {
        const response = await auth.post(`/game/${matchId}/forfeit`);
        return response;
    },

    /**
     * Subscribe to real-time board updates using Laravel Echo (Reverb).
     * Now uses the user's private channel to ensure tactical privacy.
     * @param {string} matchId 
     * @param {Function} callback 
     */
    subscribeToBoard(matchId, callback) {
        if (!window.Echo) {
            console.error('Laravel Echo is not initialized.');
            return;
        }

        const user = this.getAuthUser();
        if (!user || !user.ws_channel_key) {
            console.error('User WebSocket channel key not found.');
            return;
        }
        
        // Listen on the private user channel for tactical updates
        return window.Echo.private(`user.${user.ws_channel_key}`)
            .listen('.board.updated', (event) => {
                // Unwrap standard envelope [[api_standard_envelope]]
                const payload = event.data || event;
                
                // Filter events to ensure they belong to the current match
                if (payload.match_id === matchId) {
                    callback(payload);
                }
            });
    },

    /**
     * Unsubscribe from board updates.
     * @param {string} matchId 
     */
    unsubscribeFromBoard(matchId) {
        if (!window.Echo) return;
        const user = this.getAuthUser();
        if (user && user.ws_channel_key) {
            window.Echo.leave(`user.${user.ws_channel_key}`);
        }
    },

    /** Helper to get current user */
    getAuthUser() {
        const user = localStorage.getItem('upsilon_user');
        return user ? JSON.parse(user) : null;
    }
};

export default game;
