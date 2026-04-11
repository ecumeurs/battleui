import { reactive } from 'vue';

/**
 * @spec-link [[api_websocket_game_events]]
 */
export const connection = reactive({
    isPrivateLinked: false,
    isBoardLinked: false,
    
    setPrivateLinked(status) {
        this.isPrivateLinked = status;
    },
    
    setBoardLinked(status) {
        this.isBoardLinked = status;
    }
});
