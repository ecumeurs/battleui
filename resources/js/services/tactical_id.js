import { v7 as uuidv7 } from 'uuid';

/** @spec-link [[requirement_customer_user_id_privacy]] */

const TACTICAL_ID_KEY = 'upsilon_tactical_id';

/**
 * Gets or generates a persistent Tactical ID for the current browser session.
 * This ID is used for UI display purposes and is not linked to the database user ID.
 * @returns {string} The persistent Tactical ID
 */
export const getTacticalId = () => {
    let id = localStorage.getItem(TACTICAL_ID_KEY);
    
    if (!id) {
        id = uuidv7();
        localStorage.setItem(TACTICAL_ID_KEY, id);
    }
    
    return id;
};

/**
 * Clears the Tactical ID (useful on logout if desire to reset the identity display).
 */
export const clearTacticalId = () => {
    localStorage.removeItem(TACTICAL_ID_KEY);
};
