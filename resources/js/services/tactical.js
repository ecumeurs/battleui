/**
 * Upsilon Tactical Engine Support
 * Providing semantic helpers for game state interrogation.
 * @spec-link [[ui_battle_arena]]
 */

export const tactical = {
    /**
     * Resolves the player object for the current authenticated user.
     * @param {Object} gameState
     * @returns {Object|null}
     */
    myPlayer(gameState) {
        if (!gameState || !gameState.players) return null;
        return gameState.players.find(p => p.is_self) || null;
    },

    /**
     * Resolves the player object for the player whose turn it currently is.
     * @param {Object} gameState
     * @returns {Object|null}
     */
    currentPlayer(gameState) {
        if (!gameState || !gameState.players) return null;
        if (gameState.current_player_is_self) {
            return this.myPlayer(gameState);
        }
        
        // Find owner of the current entity
        for (const p of gameState.players) {
            if (p.entities && p.entities.some(e => e.id === gameState.current_entity_id)) {
                return p;
            }
        }
        return null;
    },

    /**
     * Returns the active unit (entity) object.
     * @param {Object} gameState
     * @returns {Object|null}
     */
    currentCharacter(gameState) {
        if (!gameState || !gameState.players) return null;
        for (const p of gameState.players) {
            const entity = p.entities.find(e => e.id === gameState.current_entity_id);
            if (entity) return entity;
        }
        return null;
    },

    /**
     * Returns all units (entities) owned by the current user.
     * @param {Object} gameState
     * @returns {Array}
     */
    myCharacters(gameState) {
        const me = this.myPlayer(gameState);
        return me ? me.entities || [] : [];
    },

    /**
     * Returns all players on the same team as the current user (excluding self).
     * @param {Object} gameState
     * @returns {Array}
     */
    myAllies(gameState) {
        const me = this.myPlayer(gameState);
        if (!me) return [];
        return gameState.players.filter(p => p.team === me.team && !p.is_self);
    },

    /**
     * Returns all entities owned by allies (team members excluding self).
     * @param {Object} gameState
     * @returns {Array}
     */
    myAlliesCharacters(gameState) {
        return this.myAllies(gameState).flatMap(p => p.entities || []);
    },

    /**
     * Returns all players on a different team.
     * @param {Object} gameState
     * @returns {Array}
     */
    myFoes(gameState) {
        const me = this.myPlayer(gameState);
        if (!me) return [];
        return gameState.players.filter(p => p.team !== me.team);
    },

    /**
     * Returns all entities owned by foes.
     * @param {Object} gameState
     * @returns {Array}
     */
    myFoesCharacters(gameState) {
        return this.myFoes(gameState).flatMap(p => p.entities || []);
    },

    /**
     * Returns the content of a specific grid cell.
     * @param {Object} gameState
     * @param {number} x
     * @param {number} y
     * @returns {Object|null}
     */
    cellContentAt(gameState, x, y) {
        if (!gameState || !gameState.grid || !gameState.grid.cells) return null;
        if (x < 0 || x >= gameState.grid.width || y < 0 || y >= gameState.grid.height) return null;
        
        const cell = gameState.grid.cells[x][y];
        if (!cell.entity_id) return { obstacle: cell.obstacle, entity: null };

        // Find entity in players
        let foundEntity = null;
        for (const p of gameState.players) {
            foundEntity = p.entities.find(e => e.id === cell.entity_id);
            if (foundEntity) break;
        }
        
        return {
            obstacle: cell.obstacle,
            entity: foundEntity
        };
    }
};

export default tactical;
