---
id: ui_action_panel
status: STABLE
type: UI
version: 1.1
parents:
  - [[shared:req_skill_generation_overhaul]]
  - [[ui_battle_arena]]
  - [[upsilonbattle:mech_action_economy]]
dependents: []
human_name: Action Panel Component
layer: ARCHITECTURE
priority: 4
tags: [ui, combat, actions, turn, skills]
---

# New Atom

## INTENT
A self-contained, boxed combat action panel that enables or disables player actions depending on whether the authenticated user owns the current turn.

## THE RULE / LOGIC
- **Box Structure:** Outer border container with a status header row and a button row.
- **Header row contains:**
  - Status pill: `YOUR TURN` (cyan, pulsing dot) or `WAITING` (muted, gray dot) based on `isPlayerTurn`.
  - Character context: active entity name, HP, movement remaining.
  - Owner label: nickname of the player whose turn it is, or `⬡ Sending…` while processing.
- **Button row:** MOVE (+20/tile), ATTACK (+100), PASS (+300), then the **active skill row**, then a separator, then FORFEIT.
- **Active skill row:** One button per equipped skill with `behavior ∈ {Direct, Reaction, Counter, Trap}`. Each button shows `<SkillIcon>` (32×32) + stacked name/cost label. Disabled when skill is on cooldown or resources are insufficient. Click emits `('action', { type: 'skill', skillId })`.
- **Passive skill rail:** Equipped skills with `behavior == Passive` are rendered below the button row as a thin, non-interactive rail. Icon (20×20) + name only; `cursor: default`; faint pulse animation signals "always active". Hover surfaces `<SkillDetail>` tooltip for inspection only. No click handler.
- **Disabled state:** When `!isPlayerTurn` or `isProcessing`, the button row gets `opacity: 0.28`, `filter: saturate(0.3) brightness(0.7)`, and `pointer-events: none`. A translucent lock overlay appears below the header with the text `Actions locked — awaiting your turn`.
- **Active state:** When `isPlayerTurn`, the outer box glows with a subtle cyan border/shadow.
- **Turn authority is determined at the player level:** `isPlayerTurn = (current_player_id === authenticated_user.id)`. Any character belonging to that player activates the panel.
- **Selected action highlight:** MOVE and ATTACK buttons show a colored selected state when clicked, cleared on board update or cancel.");

## TECHNICAL INTERFACE
- **Code Tag:** `@spec-link [[ui_action_panel]]`
- **File:** `resources/js/Components/Arena/ActionPanel.vue`
- **Props:** `isPlayerTurn`, `isProcessing`, `canMove`, `canAttack`, `moveCostPerTile`, `attackCost`, `passCost`, `selectedAction`, `activeCharacter`, `activePlayerName`, `equippedSkills: SkillSnapshot[]`
- **Emits:** `action` with payload `{ type: 'move' | 'attack' | 'pass' | 'forfeit' | 'skill', skillId?: string }`

## EXPECTATION
- Panel renders correctly in both YOUR TURN and WAITING states.
- In WAITING state: button row is visually grayed out (desaturated + dimmed), overlay text appears, status pill shows muted WAITING.
- In YOUR TURN state: box border glows cyan, status pill has pulsing cyan dot, buttons are fully interactive.
- Character context (name, HP, MOV) is visible in the header for the currently active entity.
- Active player nickname is shown on the right of the header when it is not the user's turn.
- FORFEIT button requires only `isPlayerTurn`; MOVE additionally requires `canMove`; ATTACK requires `canAttack`.
- Active skill buttons are disabled when `!isPlayerTurn`, when the skill cooldown > 0, or when activation resources are insufficient.
- Passive skill rail entries have no click handler and remain visually distinct (dimmed, pulsing) regardless of turn state.
- All actions are completely unreachable (pointer-events: none) when locked.
- `equippedSkills` prop is an empty array when no skills are equipped; panel renders without the skill row in that case.
