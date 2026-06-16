/**
 * useBattleChannel — WebSocket/Echo subscription for the battle board.
 *
 * Handles:
 *  - Subscribing to live board updates via game.subscribeToBoard
 *  - Tracking connection health via window.Echo pusher state
 *  - Cleaning up subscription on unmount
 *
 * @param {import('vue').Ref<string|null>} matchId
 * @param {import('vue').Ref<object|null>} gameState  — written by this composable
 * @param {import('vue').Ref<object|null>} selectedAction  — cleared on board update
 * @param {import('vue').Ref<array>}        selectedPath    — cleared on board update
 * @param {import('vue').Ref<array>}        highlightedCells — cleared on board update
 */
import { ref, onMounted, onUnmounted } from 'vue';
import { game } from '@/services/game';
import { connection } from '@/services/connection';

export function useBattleChannel(matchId, gameState, selectedAction, selectedPath, highlightedCells) {
    const isSocketConnected = ref(false);

    function handleBoardUpdate(event) {
        console.log('[BoardUpdated] Received payload:', event);
        if (!gameState.value || (event.version && event.version >= (gameState.value.version || 0))) {
            gameState.value = event;
            connection.setBoardLinked(true);
            selectedAction.value = null;
            selectedPath.value = [];
            highlightedCells.value = [];
        } else {
            console.warn('[Arena] Ignoring older version update from socket', event.version, 'vs', gameState.value?.version);
        }
    }

    function wireConnectionHealth() {
        if (window.Echo && window.Echo.connector.pusher.connection) {
            isSocketConnected.value = window.Echo.connector.pusher.connection.state === 'connected';
            window.Echo.connector.pusher.connection.bind('state_change', (states) => {
                isSocketConnected.value = states.current === 'connected';
                console.log('[PusherState]', states.current);
            });
        }
    }

    function subscribe() {
        if (!matchId.value) return;
        game.subscribeToBoard(matchId.value, handleBoardUpdate);
    }

    function unsubscribe() {
        if (matchId.value) {
            game.unsubscribeFromBoard(matchId.value);
            connection.setBoardLinked(false);
        }
    }

    onMounted(subscribe);
    onUnmounted(unsubscribe);

    return {
        isSocketConnected,
        wireConnectionHealth,
    };
}
