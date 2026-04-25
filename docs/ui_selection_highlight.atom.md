---
id: ui_selection_highlight
human_name: "Tactical Selection Highlight"
type: UI
layer: ARCHITECTURE
version: 1.0
status: STABLE
priority: 5
tags: [ui, combat, board, highlight, selection]
parents:
  - [[ui_iso_board]]
dependents: []
---

# Tactical Selection Highlight

## INTENT
To provide clear, high-fidelity visual feedback for tile-based tactical actions (movement, attack targeting) using a dynamic pulsing effect.

## THE RULE / LOGIC
- **Geometry:** A circular disc (clipping a square plane via shader `discard`).
- **Pulsing Animation:**
  - **Move:** Static or slow pulse (`0.005` amplitude).
  - **Attack:** High-energy pulse (`0.02` amplitude) to indicate threat.
- **Color Coding:**
  - **Cyan (#00f2ff):** Valid movement range.
  - **Magenta (#ff00ff):** Valid attack target selection.
- **Edge Glow:** A smoothstep-based border gradient (`dist 0.45 to 0.5`) to create a soft "neon" ring.

## TECHNICAL INTERFACE (The Bridge)
- **Code Tag:** `@spec-link [[ui_selection_highlight]]`
- **Component:** `GridHighlight.vue`, `HighlightMaterial.vue`
- **Uniforms:**
  - `uTime`: Elapsed time.
  - `uColor`: Highlight color.
  - `uIsAttack`: Boolean float (1.0 for attack) driving pulse intensity.

## EXPECTATION (For Testing)
- Selection tiles appear as perfect circles on the board.
- No black corners are visible on the highlight planes.
- Attack highlights pulse more dramatically than movement highlights.
- Highlights are anchored `0.02` units above the terrain surface to avoid Z-fighting.
