---
id: ui_character_equipment_panel
status: DRAFT
priority: 5
parents:
  - [[req_ui_look_and_feel]]
  - [[ui_dashboard]]
  - [[ui_theme]]
  - [[upsilonapi:api_equipment_management]]
dependents: []
version: 2.0
tags: [ui, character, equipment, dashboard, iss-074]
human_name: Character Equipment Panel UI
type: UI
layer: ARCHITECTURE
---

# New Atom

## INTENT
To surface the 3-slot equipment configuration inline on each character card on the dashboard, with a click-to-equip drawer affordance. Tightly coupled to the character stat panel so equipment contributions are visible alongside the stats they modify.

## THE RULE / LOGIC
**Component layout:**
- `Components/Character/EquipmentSlotPill.vue` — a single slot widget. Props `{ slot, item, onClick }`. Renders item icon + name when populated, "Empty" placeholder otherwise. Emits `@click` to open `EquipDrawer` for that slot.
- `Components/Character/CharacterEquipmentPanel.vue` — composes 3 `EquipmentSlotPill` (armor / utility / weapon) horizontally below the stat panel.

**Theme:** Pills are rounded-rect with cyan border when populated, dimmed steel when empty. Hover: glow effect per ui_theme.

**Interaction:**
- Click on a populated slot → opens `EquipDrawer` pre-filtered to swap that slot.
- Click on an empty slot → opens `EquipDrawer` to populate from compatible items.
- Long-press / right-click → unequip directly (with confirmation).

**State source:** `CharacterResource.equipment` (joined `character_equipment` payload from Laravel).

**Empty-state copy:** Sci-fi flavor — e.g. "AWAITING ARMOR LINK", "WEAPON BAY EMPTY".

## TECHNICAL INTERFACE
- **Code Tag:** `@spec-link [[ui_character_equipment_panel]]`
- **Components:** `resources/js/Components/Character/EquipmentSlotPill.vue`, `resources/js/Components/Character/CharacterEquipmentPanel.vue`

## EXPECTATION
- Each character card shows three slots with populated or empty state.
- Clicking a slot opens the EquipDrawer scoped to that character + slot.
- Equipping a new item updates the panel and the stat panel without a page reload.
