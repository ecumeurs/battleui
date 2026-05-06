---
id: ui_diagnostic_terminal
human_name: Diagnostic Terminal Panel
type: UI
layer: ARCHITECTURE
status: DRAFT
version: 1.0
priority: 4
tags: [ui, dashboard, character]
parents:
  - [[req_ui_look_and_feel]]
  - [[ui_character_full_stat_panel]]
  - [[ui_dashboard]]
dependents: []
---

# Diagnostic Terminal Panel

## INTENT
A slide-out panel from the right edge of the dashboard that renders character details and loadout management. Replaces the blocking `CharacterDetailModal` with a non-interrupting overlay that follows the "Neon in the Dust" aesthetic.

## THE RULE / LOGIC
- Activated by selecting a character card in the roster; dismissed by clicking the backdrop or the SEVER button.
- Slide animation must use `linear` easing (no cubic-bezier spring curves).
- All visible text uses only cyan, magenta, lime, or white — never steel, void, or gunmetal.
- Panel markup: `bg-upsilon-gunmetal/90 backdrop-blur-xl`, left border cyan, 2px top accent.
- Terminology follows sci-fi/mad-max convention: SEVER (close), ESTABLISHING TACTICAL LINK (loading), LINK CORRUPTED (error).
- Exposes the same loadout management surface as the former modal: stat grid, equipment slot swap, skill slot swap.

## TECHNICAL INTERFACE
- **Code Tag:** `@spec-link [[ui_diagnostic_terminal]]`
- **Component:** `resources/js/Components/Dashboard/DiagnosticTerminal.vue`
- **Replaces:** `CharacterDetailModal.vue` (deleted)

## EXPECTATION
- Panel slides in/out in ≤ 300ms with linear easing.
- State is isolated: closing the panel does not discard the loaded character data until a new character is selected.
- Does not re-fetch inventory on open — consumes `useDashboardState` singleton.
