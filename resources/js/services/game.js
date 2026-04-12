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
    async sendAction(matchId, playerId, entityId, type, targetCoords = []) {
        const payload = {
            player_id: playerId,
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
     * @param {string} matchId 
     * @param {Function} callback 
     */
    subscribeToBoard(matchId, callback) {
        if (!window.Echo) {
            console.error('Laravel Echo is not initialized.');
            return;
        }

        const token = localStorage.getItem('upsilon_token');
        if (token) {
            window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        }
        
        return window.Echo.private(`arena.${matchId}`)
            .listen('.board.updated', callback);
    },

    /**
     * Unsubscribe from board updates.
     * @param {string} matchId 
     */
    unsubscribeFromBoard(matchId) {
        if (!window.Echo) return;
        window.Echo.leave(`arena.${matchId}`);
    }
};

export default game;
