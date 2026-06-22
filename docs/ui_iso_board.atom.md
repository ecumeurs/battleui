---
id: ui_iso_board
status: STABLE
type: UI
layer: ARCHITECTURE
version: 2.0
tags: [ui, combat, board, threejs, 3d, terrain, hex]
dependents:
  - [[mech_frontend_test_seams]]
  - [[ui_character_pawn]]
  - [[ui_holo_obstacle]]
  - [[ui_selection_highlight]]
human_name: 3D Hexagonal Board Grid UI (Three.js)
priority: 5
parents:
  - [[ui_battle_arena]]
  - [[upsilonbattle:mech_board_generation]]
---

# Board Grid UI

## INTENT
The real-time 3D board renderer displaying a dynamic NxM tile grid with terrain elevation, obstacles, character pawns, movement range highlights, and attack target highlights — built on Three.js via `@tresjs/core`.

## THE RULE / LOGIC
- **Rendering:** Three.js real-time 3D scene via `<TresCanvas>` from `@tresjs/core`.
- **Digital Hex Terrain:** The ground is rendered as a subdivided hexagonal grid. Each logical square tile is visually represented by a cluster of approximately 16 hexagonal columns (4x4 subdivision).
- **Seamless Meshing:** Hexagonal columns align perfectly across logical tile boundaries by using a global hex coordinate system.
- **Coordinate mapping:** 
    - Logical $(gx, gy)$ determines which hexes belong to a tile.
    - Each hex $(hx, hy)$ has a world position $(X, Z)$ based on flat-topped hex math.
    - A hex belongs to tile $(gx, gy)$ if $gx \le X < gx+1$ and $gy \le Z < gy+1$.
- **Cell elevation:** Each hex column rises from $Y=0$ to $Y = height \times TILE\_HEIGHT$.
- **Tile Types:**
    - Normal: `TresCylinderGeometry` (6 segments), dark gunmetal (`#2a2a2a`).
    - Obstacle: taller hexagonal columns in rusty brown (`#3d2b1f`).
- **Pawns & Highlights:** Positioned relative to the logical grid centers $(gx, gy)$.

## TECHNICAL INTERFACE
- **Code Tag:** `@spec-link [[ui_iso_board]]`
- **Component:** `ThreeGrid.vue` (replaces `IsoBoardGrid.vue`)
- **Props:**
    - `grid`: `{ width, height, cells: cells[x][y] }` where each cell is `{ height: number, obstacle: bool, entity_id?: string }`
    - `entities[]`: `{ id, position: {x,y}, hp, dead, team, player_id, nickname, is_self }`
    - `currentEntityId`: string (active-turn entity, drives emissive boost)
    - `teamColors`: `{ [player_id | nickname]: hexColor }`
    - `highlightedCells[]`: `{ x, y, type: 'move' | 'attack' }`
- **Emits:** `tile-click(x, y)`

## EXPECTATION
- Board renders as a visually correct 3D scene with terrain elevation visible.
- Elevated tiles appear higher on the Y-axis than flat tiles; obstacles extrude above their tile surface.
- Obstacle tiles are visually distinct from walkable tiles.
- Character pawns appear at correct 3D positions (atop their tile's surface height).
- Active-turn pawn glows brighter than idle pawns.
- Board handles grid sizes from 5×5 to 15×15.
- `/__test/component/grid-elevated` fixture demonstrates the height dimension explicitly.
