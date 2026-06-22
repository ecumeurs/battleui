---
id: ui_dashboard
human_name: Dashboard Page UI
type: UI
layer: ARCHITECTURE
version: 1.0
status: STABLE
priority: 5
tags: []
parents:
  - [[module_frontend]]
  - [[uc_player_login]]
dependents:
  - [[uc_player_login]]
  - [[uc_progression_stat_allocation]]
  - [[ui_character_equipment_panel]]
  - [[ui_character_full_stat_panel]]
  - [[ui_character_roster]]
  - [[ui_dashboard_match_statistics]]
  - [[ui_dashboard_matchmaking]]
  - [[ui_dashboard_profile_edit]]
  - [[ui_diagnostic_terminal]]
  - [[ui_tactical_layout]]
---
# Dashboard Page UI

## INTENT
To render the primary logged-in hub where authenticated players review their roster and personal statistics, navigate to the leaderboard, and initiate matchmaking.

## THE RULE / LOGIC
Serves as the primary logged-in hub. The dashboard shell itself enforces and renders the following:

- **Security gate:** Requires a valid JWT to access; unauthenticated requests are redirected to login.
- **Player statistics:** Permanently displays the player's total match Wins, total match Losses, and calculated Win/Loss ratio.
- **Roster:** Presents the player's 3 characters with their stats (HP, Movement, Attack, Defense). Roster rendering and mutation are delegated to `[[ui_character_roster]]`.
- **Queue selection:** Presents 4 distinct buttons to start a new game (the four game modes). Queue/match orchestration is handled by `[[ui_dashboard_matchmaking]]`.
- **Leaderboard navigation:** Provides a clear entry point to the Global Leaderboard (`[[ui_leaderboard]]`), embedded below the Match Type Selector.

Richer sub-surfaces remain as their own atoms: `[[ui_dashboard_matchmaking]]`, `[[ui_dashboard_match_statistics]]`, `[[ui_dashboard_profile_edit]]`.

## TECHNICAL INTERFACE (The Bridge)
- **Code Tag:** `@spec-link [[ui_dashboard]]`
