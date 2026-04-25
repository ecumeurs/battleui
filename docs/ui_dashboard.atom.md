---
id: ui_dashboard
human_name: Dashboard Page UI
type: MODULE
layer: ARCHITECTURE
version: 1.0
status: STABLE
priority: 5
tags: []
parents:
  - [[module_frontend]]
  - [[uc_player_login]]
dependents:
  - [[ui_character_roster]]
  - [[ui_dashboard_match_statistics]]
---
# Dashboard Page UI

## INTENT
To aggregate the constituent rules of Dashboard Page UI.

## THE RULE / LOGIC
Serves as a primary logged-in hub where players review their roster and initiate matchmaking.

## TECHNICAL INTERFACE (The Bridge)
- **Code Tag:** `@spec-link [[ui_dashboard]]`
