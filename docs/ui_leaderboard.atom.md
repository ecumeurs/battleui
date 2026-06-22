---
id: ui_leaderboard
human_name: Leaderboard Page UI
type: UI
layer: ARCHITECTURE
version: 1.0
status: STABLE
priority: 5
tags: []
parents:
  - [[shared:req_tech_debt_backlog]]
dependents:
  - [[upsilonapi:api_leaderboard]]
---
# Leaderboard Page UI

## INTENT
To render the Global Leaderboard surface that showcases player rankings and statistics, integrated into the main Dashboard view directly below the Match Type Selector.

## THE RULE / LOGIC
**Placement & Integration**
- The leaderboard component is integrated into the main Dashboard view, positioned directly below the Match Type Selector.

**Modes (categories)**
- Supports 4 distinct categories, switchable via a toggle/tab interface: 1v1 PvP, 2v2 PvP, 1v1 PvE, 2v2 PvE.
- Statistics for one mode do not bleed into another.

**Data Display**
- Presents a sorted, paginated list of players globally, exactly 10 entries per page.
- Always displays the authenticated user's own position, statistics, and rank (pinned at top or bottom), even when they are not on the current results page.
- Empty state (zero data): themed message "SENSORS OFFLINE: NO DATA RECOVERED" or "AREA SCAVENGED: NO SIGNS OF LIFE".
- Search must always provide feedback: if no matches (e.g. user has 0 matches in the mode), display "COMMUNICATIONS JAMMED: NO SIGNATURE FOUND".

**Metrics per row**
- Each row displays: Player Account Name, Total Wins, Total Losses, and Win/Loss Ratio.

**Sorting**
- Primary sort: total Wins, descending.
- Secondary sort (tie-break on equal Wins): highest Win/Loss ratio first.

**Security**
- Requires a valid JWT to access; backed by `[[upsilonapi:api_leaderboard]]`.

## TECHNICAL INTERFACE (The Bridge)
- **Component:** `resources/js/Components/Dashboard/LeaderboardComponent.vue`
- **API:** `[[upsilonapi:api_leaderboard]]`
- **Code Tag:** `@spec-link [[ui_leaderboard]]`
