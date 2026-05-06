// @spec-link [[ui_battle_arena]]
// @spec-link [[api_websocket_game_events]]
import auth from './auth';
import { connection } from './connection';

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
     * @param {string} entityId
     * @param {string} type 'move' | 'attack' | 'pass' | 'skill'
     * @param {Array<{x: number, y: number}>} targetCoords
     * @param {Object} extra  additional payload fields merged at root level (e.g. { skill_id })
     * @returns {Promise<Object>}
     */
    async sendAction(matchId, entityId, type, targetCoords = [], extra = {}) {
        const payload = {
            entity_id: entityId,
            type,
            ...extra,
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
            throw new Error('CRITICAL: Laravel Echo is not initialized. Real-time updates unavailable.');
        }

        const user = this.getAuthUser();
        if (!user || !user.ws_channel_key) {
            throw new Error('CRITICAL: User WebSocket channel key not found. Tactical link cannot be established.');
        }
        
        // Listen on the private user channel for tactical updates
        const channel = window.Echo.private(`user.${user.ws_channel_key}`);
        channel.subscribed(() => connection.setBoardLinked(true));

        const events = ['.board.updated', '.game.started', '.turn.started', '.game.ended', '.game.forfeited'];
        
        events.forEach(eventName => {
            channel.listen(eventName, (event) => {
                // Unwrap standard envelope [[api_standard_envelope]]
                const payload = event.data || event;
                
                // Filter events to ensure they belong to the current match
                if (payload.match_id === matchId) {
                    console.log(`[Tactical] Received ${eventName}:`, payload);
                    callback(payload);
                }
            });
        });

        return channel;
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
