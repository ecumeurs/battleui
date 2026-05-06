// @spec-link [[api_websocket_user_notifications]]
import { watch, onUnmounted } from 'vue';
import { connection } from '@/services/connection';

/**
 * Manages the private-user WebSocket channel subscription for an authenticated session.
 * Call once from the page that owns the authenticated session (Dashboard).
 * Child components can attach listeners via window.Echo.private() without calling leave().
 */
export function usePrivateChannel(userRef) {
    let channel = null;

    function leave(key) {
        if (window.Echo && key) window.Echo.leave(`user.${key}`);
        channel = null;
        connection.setPrivateLinked(false);
    }

    watch(
        () => userRef.value?.ws_channel_key,
        (newKey, oldKey) => {
            // Always clean up the previous subscription first
            if (oldKey) leave(oldKey);

            if (!newKey || !window.Echo) return;

            channel = window.Echo.private(`user.${newKey}`)
                .subscribed(() => connection.setPrivateLinked(true))
                .error(() => {
                    console.error('[usePrivateChannel] Subscription auth failed');
                    connection.setPrivateLinked(false);
                    channel = null;
                });
        },
        { immediate: true }
    );

    onUnmounted(() => leave(userRef.value?.ws_channel_key));
}
