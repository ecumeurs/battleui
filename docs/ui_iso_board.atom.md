---
id: ui_iso_board
status: REVIEW
type: UI
layer: ARCHITECTURE
version: 2.0
tags: [ui, combat, board, threejs, 3d, terrain]
dependents:
  - [[mech_frontend_test_seams]]
  - [[ui_character_pawn]]
  - [[ui_holo_obstacle]]
human_name: 3D Board Grid UI (Three.js)
priority: 5
parents:
  - [[mech_board_generation_board_dimensions]]
  - [[ui_battle_arena]]
---

# Board Grid UI

## INTENT
The real-time 3D board renderer displaying a dynamic NxM tile grid with terrain elevation, obstacles, character pawns, movement range highlights, and attack target highlights — built on Three.js via `@tresjs/core`.

## THE RULE / LOGIC
- **Rendering:** Three.js real-time 3D scene via `<TresCanvas>` from `@tresjs/core`. Replaces the former CSS 3D isometric transform approach.
- **Coordinate system:** X = grid column, Y = terrain elevation, Z = grid row. Grid cells stored width-major: `cells[x][y]`.
- **Cell elevation (new):** Each cell carries an integer `height ≥ 0`. The tile Y-position is computed as `height * TILE_HEIGHT` where `TILE_HEIGHT = 0.25` world-units. This creates genuine 3D terrain variation — cells at height 2 sit visually above cells at height 0.
- **Grid Size:** Dynamic based on `width` and `height` props (5–15 tiles per axis).
- **Tile Types:**
    - Normal: `TresBoxGeometry` slab, dark gunmetal (`#2a2a2a`), roughness 0.8.
    - Obstacle: additional extruded block (`TILE_HEIGHT * 2` tall) above the surface in rusty brown (`#3d2b1f`).
    - Move-Highlighted: thin semi-transparent cyan slab (`#00f2ff`, emissive 0.6) hovering 0.05u above surface.
    - Attack-Highlighted: thin semi-transparent magenta slab (`#ff00ff`, emissive 0.6) hovering above surface.
- **Pawns:** `TresConeGeometry` (6-sided, radius 0.3, height 0.8) positioned above the surface at `height * TILE_HEIGHT + TILE_HEIGHT/2 + 0.4`. Color tinted per team; active-turn entity receives emissive intensity 0.8 vs 0.2 for idle.
- **Camera:** `TresPerspectiveCamera` with 45° FOV, positioned diagonally above grid center. `OrbitControls` for manual inspection.
- **Lighting:** `TresAmbientLight` (0.4) + `TresDirectionalLight` (0.9, cast-shadow).

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
