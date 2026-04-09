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
- **ATD:** *(no dedicated atom — part of ui_battle_arena)*
- **Description:** Contextual action buttons: Move (+20/tile), Attack (+100), Pass (+300), Forfeit. Each shows delay cost. Disabled when not the player's turn. Color-coded hover states.
- **Props:** `isPlayerTurn`, `canMove`, `canAttack`, `moveCostPerTile`, `attackCost`, `passCost`

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

## What Remains To Be Done

### Phase 2 — Visual Polish (Design Iteration)
- [ ] Fix isometric tile tessellation (tiles forming a proper uniform diamond grid)
- [ ] Improve CharacterPawn 3D appearance (faceted cone sides, rotation)
- [ ] Add dust/noise texture overlays to board tiles
- [ ] Animate HP bar changes in CombatHeader on state updates
- [ ] Add ambient board edge glow effects
- [ ] Responsive scaling for different viewports

### Phase 3 — WebSocket Integration
- [ ] Replace mock data with real `BoardState` from Go engine via `/api/v1/game/{match_id}`
- [ ] Subscribe to `BoardUpdated` Reverb channel for real-time state pushes
- [ ] Wire shot clock to server-side `timeout` field
- [ ] Wire match timer to server-side `start_time`
- [ ] Handle turn transitions (active character highlight changes)

### Phase 4 — Action Implementation
- [ ] Move action: click tile → highlight path → confirm → POST action
- [ ] Attack action: highlight adjacent enemies → click target → POST action
- [ ] Pass action: confirm dialog → POST action
- [ ] Forfeit action: multi-step confirm → POST action
- [ ] Display movement range (based on character's remaining `move` stat)
- [ ] Display attack targets (adjacent cells with enemy entities)
- [ ] Show delay cost preview in InitiativeTimeline (shadow positions)

### Phase 5 — Game Flow
- [ ] End-of-game detection (`winner_id` populated)
- [ ] Victory/defeat overlay
- [ ] Match result screen with stats
- [ ] Return to dashboard navigation

### Phase 6 — ATD Finalization
- [ ] Update `ui_board` atom to reference `ui_battle_arena` as its implementation
- [ ] Promote atoms from DRAFT → REVIEW → STABLE as features complete
- [ ] Add `@spec-link` tags to all implemented source files
- [ ] Run `atd_trace` verification on all new atoms
- [ ] Create `@test-link` annotations for integration tests
