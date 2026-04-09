# Battle Arena — Component Architecture

The Battle Arena is the core gameplay screen of Upsilon Battle, where tactical RPG combat takes place on an isometric grid board.

## Route
- **URL:** `/battlearena?match_id=:id`
- **Auth:** Uses `TacticalLayout` (JWT via header)
- **File:** [BattleArena.vue](file:///workspace/battleui/resources/js/Pages/BattleArena.vue)

---

## ATD Ancestry

```
CUSTOMER LAYER
├── req_player_experience (STABLE)
│   └── req_trpg_game_definition (DRAFT) ← NEW: defines what a TRPG is
│       └── ui_battle_arena (DRAFT) ← NEW: the page itself
└── req_ui_look_and_feel (DRAFT) ← "Neon in the Dust" aesthetic
    └── ui_theme (STABLE) ← color palette, fonts, readability rules

ARCHITECTURE LAYER
├── ui_board (REVIEW) ← original board page spec
│   └── ui_battle_arena (DRAFT) ← page orchestrator
│       ├── ui_combat_header (DRAFT) ← HP bars + timers
│       ├── ui_team_roster_panel (DRAFT) ← side panels
│       │   └── ui_character_battle_card (DRAFT) ← individual card
│       ├── ui_iso_board (DRAFT) ← isometric grid
│       │   └── ui_character_pawn (DRAFT) ← holographic figure
│       └── ui_initiative_timeline (DRAFT) ← turn order bar
├── mech_action_economy (STABLE) ← delay costs
│   ├── mech_action_economy_action_cost_rules ← Move +20, Attack +100, Pass +300
│   └── mech_action_economy_timeout_penalty_rules ← Timeout +400
└── mech_board_generation_board_dimensions ← 5-15 tiles per axis
```

---

## Page Layout

```
┌─────────────────────────────────────────────────────────────────────┐
│                         CombatHeader                                │
│  [◆◆◆] [████████████ HP ████████████] 04:32 [████ HP ████] [◆◆◆]  │
│                              ⏱ 23s                                  │
├──────────┬──────────────────────────────────────────┬───────────────┤
│          │                                          │               │
│  Team    │         IsoBoardGrid                     │   Adversary   │
│  Roster  │     (3D Isometric View)                  │   Roster      │
│  Panel   │                                          │   Panel       │
│  (Left)  │     + Zoom/Pan controls                  │   (Right)     │
│          │     + CharacterPawns                     │               │
│ Detailed │                                          │  Compact      │
│ Stats    │                                          │  Stats        │
│          ├──────────────────────────────────────────┤               │
│          │           ActionPanel                     │               │
│          │  [MOVE +20] [ATTACK +100] [PASS] | [⚠]  │               │
├──────────┴──────────────────────────────────────────┴───────────────┤
│                      InitiativeTimeline                             │
│  ● Vex-7(0) ──── ● Rex-4(45) ──── ● Ash-3(60) ──── ● Cinder(80)  │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Components

### 1. CombatHeader
- **File:** [CombatHeader.vue](file:///workspace/battleui/resources/js/Components/Arena/CombatHeader.vue)
- **ATD:** [ui_combat_header](file:///workspace/docs/ui_combat_header.atom.md)
- **Description:** Fighting-game-style header with HP bars for each team. HP bars transition green → orange → red based on team total HP percentage. Center shows match duration (ticks every second) and shot clock (30s countdown; orange at ≤10s, red at ≤5s with neon pulse).
- **Props:** `allyTeamHp`, `allyTeamMaxHp`, `allyCharsRemaining`, `allyTotalChars`, `enemyTeamHp`, `enemyTeamMaxHp`, `enemyCharsRemaining`, `enemyTotalChars`, `matchDuration`, `shotClock`

### 2. TeamRosterPanel
- **File:** [TeamRosterPanel.vue](file:///workspace/battleui/resources/js/Components/Arena/TeamRosterPanel.vue)
- **ATD:** [ui_team_roster_panel](file:///workspace/docs/ui_team_roster_panel.atom.md)
- **Description:** Side panel grouping characters by player nickname. Supports detailed and compact display based on which player is the "current" player. Left panel = allied forces, right panel = hostile forces.
- **Props:** `players[]`, `detailedPlayerId`, `teamColors`, `side`

### 3. CharacterBattleCard
- **File:** [CharacterBattleCard.vue](file:///workspace/battleui/resources/js/Components/Arena/CharacterBattleCard.vue)
- **ATD:** [ui_character_battle_card](file:///workspace/docs/ui_character_battle_card.atom.md)
- **Description:** Individual character card with name, HP bar, movement bar, ATK/DEF stats. Compact mode shows only name + HP. Color accent via prop for team coding.
- **Props:** `character`, `compact`, `accentColor`, `isActive`

### 4. IsoBoardGrid
- **File:** [IsoBoardGrid.vue](file:///workspace/battleui/resources/js/Components/Arena/IsoBoardGrid.vue)
- **ATD:** [ui_iso_board](file:///workspace/docs/ui_iso_board.atom.md)
- **Description:** Pure CSS isometric board renderer. SVG diamond tiles with neon-colored strokes. Supports zoom (mouse wheel + buttons), pan (arrow buttons), and reset. Tile types: normal, obstacle, move-highlighted (cyan), attack-highlighted (magenta).
- **Props:** `grid`, `entities`, `currentEntityId`, `teamColors`, `highlightedCells`

### 5. CharacterPawn
- **File:** [CharacterPawn.vue](file:///workspace/battleui/resources/js/Components/Arena/CharacterPawn.vue)
- **ATD:** [ui_character_pawn](file:///workspace/docs/ui_character_pawn.atom.md)
- **Description:** Holographic 3D figure (inverted cone body + sphere head). Includes holographic glitch/static animation, scanline overlay, team color coding (blue/green/red/purple with shade variations per character), floating name label, and HP bar overlay.
- **Props:** `entity`, `teamColor`, `isActive`, `shadeOffset`

### 6. InitiativeTimeline
- **File:** [InitiativeTimeline.vue](file:///workspace/battleui/resources/js/Components/Arena/InitiativeTimeline.vue)
- **ATD:** [ui_initiative_timeline](file:///workspace/docs/ui_initiative_timeline.atom.md)
- **Description:** Horizontal bar showing turn order. Character tokens positioned by delay value (lower = sooner). Active character (delay=0) has enhanced glow. Uses team colors.
- **Props:** `turns[]`, `teamColors`, `currentEntityId`

### 7. ActionPanel
- **File:** [ActionPanel.vue](file:///workspace/battleui/resources/js/Components/Arena/ActionPanel.vue)
- **ATD:** [ui_action_panel](file:///workspace/docs/ui_action_panel.atom.md)
- **Description:** Fully boxed turn-state component with two visual modes:
  - **YOUR TURN** — cyan border glow, pulsing status dot, all action buttons interactive.
  - **WAITING** — desaturated/dimmed button row (`opacity: 0.28`, `filter: saturate(0.3)`), `pointer-events: none`, lock overlay text. Header shows which player currently holds the turn.
  - **Header bar** (always visible): status pill `YOUR TURN | WAITING`, active character name + HP + MOV, and the active player's nickname when it is not your turn.
  - **Lock overlay:** Shown below the header when `!isPlayerTurn`; text `Actions locked — awaiting your turn`.
- **Props:** `isPlayerTurn`, `isProcessing`, `canMove`, `canAttack`, `moveCostPerTile`, `attackCost`, `passCost`, `selectedAction`, `activeCharacter` *(entity object)*, `activePlayerName` *(string)*
- **Emits:** `action` with `'move' | 'attack' | 'pass' | 'forfeit'`

---

## Mock Data (Current Static Version)

The current implementation uses hardcoded mock data mirroring the Go engine's `BoardState` response format:

- **Grid:** 10×10 with 10 obstacle tiles
- **Players:** 4 players (2v2 mode)
  - Team 1: NeonWraith (p1, blue, detailed), DustRunner (p2, green, compact)
  - Team 2: IronVeil (p3, red, compact), GhostUnit (p4, purple, compact)
- **Entities:** 12 characters (3 per player) with varied stats
- **Turn Order:** 11 entries sorted by delay (Shard at 0HP excluded)
- **Timers:** Match clock ticking up, shot clock ticking down (both live)

---

## Team Color Mapping

| Player   | Role           | Color    | Hex       |
|----------|----------------|----------|-----------|
| p1       | Current Player | Blue     | `#00a8ff` |
| p2       | Ally           | Green    | `#39ff13` |
| p3       | Enemy 1        | Red      | `#ff2020` |
| p4       | Enemy 2        | Purple   | `#b030ff` |

---

## Live Architecture (Data Flow)

```
┌──────────────┐   HTTP webhook    ┌──────────────────┐   Reverb WS     ┌─────────────┐
│  Go Engine   │ ─────────────────►│  Laravel Backend  │ ───────────────► │  Vue.js UI  │
│  (port 8081) │                   │  (port 8000)      │                  │  (Vite HMR) │
│              │◄────── HTTP ──────│                   │◄──── HTTP ───────│             │
│  /internal/  │  startArena       │  WebhookController│  POST /game/     │ BattleArena │
│  arena/start │  sendAction       │  GameController   │  {id}/action     │             │
└──────────────┘                   └──────────────────┘                  └─────────────┘
```

**Data Flow:**
1. Matchmaking creates a `GameMatch` → calls Go engine `startArena` (see `MatchMakingController`)
2. Go engine sends webhook callbacks → `WebhookController` updates `GameMatch.game_state_cache` + broadcasts `BoardUpdated` via Reverb
3. Vue client subscribes to `PrivateChannel('arena.{matchId}')` → receives `board.updated` events with full `BoardState`
4. Player actions: Vue → `POST /api/v1/game/{id}/action` → Laravel `GameController` → Go engine → webhook → broadcast

**Key files:**
- Webhook receiver: `app/Http/Controllers/API/WebhookController.php`
- Action proxy: `app/Http/Controllers/API/GameController.php`
- API service: `app/Services/UpsilonApiService.php`
- Events: `app/Events/BoardUpdated.php` (PrivateChannel `arena.{matchId}`, event name `.board.updated`)
- Models: `app/Models/GameMatch.php` (has `game_state_cache` JSON column), `app/Models/MatchParticipant.php`
- Frontend auth: `resources/js/services/auth.js` (axios with Sanctum token from localStorage)
- Echo setup: `resources/js/bootstrap.js` (Reverb broadcaster already configured)

### Go Engine `BoardState` Shape (from `upsilonapi/api/output.go`)

```json
{
  "entities": [
    { "id": "uuid", "player_id": "uuid", "name": "Vex-7", "hp": 12, "max_hp": 15,
      "attack": 5, "defense": 3, "move": 3, "max_move": 5, "position": { "x": 1, "y": 2 } }
  ],
  "grid": { "width": 10, "height": 10, "cells": [[{"entity_id":"","obstacle":false}]] },
  "turn": [ { "player_id": "uuid", "delay": 0, "entity_id": "uuid" } ],
  "current_player_id": "uuid",
  "current_entity_id": "uuid",
  "timeout": "2026-04-09T08:05:00Z",
  "start_time": "2026-04-09T07:55:00Z",
  "winner_id": ""
}
```

### Action Request Shape (from `upsilonapi/api/input.go`)

```json
POST /api/v1/game/{match_id}/action
{
  "player_id": "uuid",
  "entity_id": "uuid",
  "type": "move|attack|pass|forfeit",
  "target_coords": [{"x": 1, "y": 2}, {"x": 1, "y": 3}]
}
```

---

## Implementation Plan — What Remains

### Phase 2 — Visual Polish (Design Iteration)
- [x] Fix isometric tile tessellation (2:1 SVG diamonds, done)
- [x] Add zoom/pan controls (done)
- [x] Fix text readability per ui_theme spec (done)

### Phase 3 — Backend Completion + WebSocket Integration

#### Backend Changes Required

**`app/Http/Controllers/API/GameController.php` — Complete `state()` method:**
```php
public function state(Request $request, string $id)
{
    $match = \App\Models\GameMatch::findOrFail($id);
    $participants = \App\Models\MatchParticipant::where('match_id', $id)
        ->with('player:id,nickname')
        ->get();

    return $this->success([
        'match_id' => $match->id,
        'game_mode' => $match->game_mode,
        'game_state' => $match->game_state_cache,
        'participants' => $participants->map(fn($p) => [
            'player_id' => $p->player_id,
            'nickname' => $p->player?->nickname ?? 'Unknown',
            'team' => $p->team,
        ]),
        'started_at' => $match->started_at?->toIso8601String(),
        'concluded_at' => $match->concluded_at?->toIso8601String(),
        'winning_team_id' => $match->winning_team_id,
    ]);
}
```

**`routes/channels.php` — Add arena channel auth:**
```php
Broadcast::channel('arena.{matchId}', function ($user, $matchId) {
    return \App\Models\MatchParticipant::where('match_id', $matchId)
        ->where('player_id', $user->id)
        ->exists();
});
```

#### Frontend Changes Required

**New file: `resources/js/services/game.js`** — Game API + WebSocket service:
- `fetchGameState(matchId)` → `GET /api/v1/game/{matchId}` (uses `auth` axios instance from `services/auth.js`)
- `sendAction(matchId, playerId, entityId, type, targetCoords?)` → `POST /api/v1/game/{matchId}/action`
- `subscribeToBoard(matchId, callback)` → `window.Echo.private('arena.' + matchId).listen('.board.updated', callback)`
- `unsubscribeFromBoard(matchId)` → `window.Echo.leave('arena.' + matchId)`

**Modify: `BattleArena.vue`** — Replace mock data with live state:
- On mount: read `match_id` from URL query param, fetch state via `game.fetchGameState(matchId)`
- Subscribe to WebSocket `BoardUpdated` → replace reactive `gameState` on each event
- Shot clock: compute from `gameState.timeout` (server timestamp) vs `Date.now()` — no client countdown needed
- Turn detection: `gameState.current_player_id === authenticatedUser.id`
- Group entities into ally/enemy by matching `entity.player_id` against `participants[].team`

**Modify: `IsoBoardGrid.vue`** — Add `@tile-click` emit with `{x, y}`

#### Checklist
- [x] Complete `GameController::state()` method
- [x] Add `arena.{matchId}` channel auth to `channels.php`
- [x] Create `services/game.js` (fetch, action, subscribe)
- [x] Rewrite `BattleArena.vue` script to use live data
- [x] Subscribe to `BoardUpdated` via Echo
- [x] Wire shot clock to `gameState.timeout`
- [x] Wire match timer to `gameState.start_time`
- [x] Handle turn transitions (active character highlight)

### Phase 4 — Action Implementation

**Move action flow:**
1. Player clicks MOVE button → `selectedAction = 'move'`
2. Board highlights reachable tiles (based on `entity.move` remaining, BFS avoiding obstacles)
3. Player clicks tiles sequentially to build path → each click appends to `selectedPath[]`
4. Confirm sends `POST /game/{id}/action` with `type: 'move'`, `target_coords: selectedPath`
5. Go engine validates path, moves entity, sends webhook → board updates

**Attack action flow:**
1. Player clicks ATTACK → `selectedAction = 'attack'`
2. Board highlights adjacent tiles containing enemies (range 1, melee only)
3. Player clicks target → sends `POST /game/{id}/action` with `type: 'attack'`, `target_coords: [{x,y}]`

**Pass/Forfeit:**
- Pass: immediate `POST` with `type: 'pass'`
- Forfeit: confirmation dialog first, then `POST` with `type: 'forfeit'`

#### Checklist
- [x] Add `@tile-click` emit to `IsoBoardGrid.vue`
- [x] Implement move mode (BFS reachable tiles, path building)
- [x] Implement attack mode (adjacent enemy highlighting)
- [x] Wire Pass button to API
- [x] Wire Forfeit button with confirm dialog
- [x] Show delay cost preview on timeline (shadow positions)
- [x] Show action loading state in ActionPanel

### Phase 5 — Game Flow
- [x] Detect end-of-game (`winner_id !== ""` in BoardState)
- [x] Display victory/defeat overlay
- [x] Match result screen with stats
- [x] Navigation back to dashboard

### Phase 6 — ATD Finalization
- [x] Promote UI atoms from DRAFT → REVIEW → STABLE as features complete
- [x] Add `@spec-link` tags to all new service files
- [x] Run `atd_trace` verification on `ui_battle_arena` (skipped per user instruction)
- [x] Create `@test-link` annotations for integration tests (skipped per user instruction)

### Phase 7 - Polish
- [x] Improve CharacterPawn 3D appearance (faceted cone sides, rotation)
- [ ] Add dust/noise texture overlays to board tiles
- [ ] Animate HP bar changes in CombatHeader on state updates
- [ ] Add ambient board edge glow effects
- [ ] Responsive scaling for different viewports
- [x] Ensure the character whose turn it is is visible. Cone should stop spinning and it's tile should glow (fading in and out) a bit (greenish) 

---

## Resolved Design Decisions

1. **Player ID Mapping**: The Go engine uses UUIDs for `player_id` but the Laravel `User.id` (integer) is safely cast/handled. We use the authenticated user's ID as the `player_id`.
2. **Movement Pathfinding**: The UI implements client-side pathfinding (BFS avoiding obstacles). The user clicks the destination tile, and the UI calculates the shortest path and sends the full array of coordinates to the engine.
3. **Attack Range**: Hardcoded to 1 (melee, adjacent tiles only) for now. The logic can easily scale if ranged attacks are added later.
