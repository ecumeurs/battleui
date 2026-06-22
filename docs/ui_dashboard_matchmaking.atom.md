---
id: ui_dashboard_matchmaking
human_name: "Dashboard Matchmaking Hub"
type: UI
layer: ARCHITECTURE
version: 1.0
status: STABLE
priority: 4
tags: [ui, matchmaking, dashboard, websocket]
parents:
  - [[ui_dashboard]]
  - [[upsilonapi:api_websocket_user_notifications]]
  - [[upsilonapi:rule_matchmaking_single_queue]]
dependents: []
has_tests: true
linked_codes:
  - battleui/resources/js/Components/Dashboard/EngagementHub.vue
  - battleui/resources/js/services/connection.js
  - battleui/tests/playwright/user_flows.spec.ts
---

# Dashboard Matchmaking Hub

## INTENT
To allow authenticated players to enter and exit game mode queues from the dashboard, and to establish and track the private WebSocket channel that delivers match-found notifications and real-time board events.

## THE RULE / LOGIC
1. **Queue Actions**: Renders four mode buttons (1v1 PVE, 1v1 PVP, 2v2 PVE, 2v2 PVP). Clicking any mode calls `POST /matchmaking/join` with the selected `game_mode`. An abort button calls `DELETE /matchmaking/leave`.
2. **Status States**: The component cycles through `idle → queued → matched`. A 5-second polling fallback (`GET /matchmaking/status`) ensures state catches up even if the WS event is missed.
3. **Private WS Subscription**: On mount (or when `user.ws_channel_key` becomes available via watcher), subscribes to `private-user.{ws_channel_key}` via Laravel Echo. On successful subscription, calls `connection.setPrivateLinked(true)`, which lights the `Link[Private]` LED in `TacticalFooter`.
4. **Match Found Redirect**: Listening to `.match.found` event on the private channel. Extracts `match_id` from the event payload and calls `router.visit('/battlearena?match_id=...')`.
5. **Cleanup**: On unmount, calls `window.Echo.leave(...)` and `connection.setPrivateLinked(false)`.

## TECHNICAL INTERFACE (The Bridge)
- **Component:** `battleui/resources/js/Components/Dashboard/EngagementHub.vue`
- **Connection State:** `battleui/resources/js/services/connection.js` (`isPrivateLinked`)
- **Footer LED:** `TacticalFooter.vue` — `data-testid="led-private"` turns lime when `isPrivateLinked === true`
- **API Endpoints:** `POST /api/v1/matchmaking/join`, `DELETE /api/v1/matchmaking/leave`, `GET /api/v1/matchmaking/status`
- **Code Tag:** `@spec-link [[ui_dashboard_matchmaking]]`

## EXPECTATION (For Testing)
- User registers or logs in → navigates to `/dashboard` → `led-private` element gains `bg-upsilon-lime` class within 15 seconds.
- User clicks "Solo / PVE" → status transitions to `queued` → `match.found` received → redirected to `/battlearena?match_id=...`.
- On unmount (navigate away), `connection.isPrivateLinked` is reset to `false`.
