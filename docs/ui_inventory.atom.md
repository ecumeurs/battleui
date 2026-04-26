---
id: ui_inventory
status: DRAFT
type: UI
layer: ARCHITECTURE
version: 2.0
human_name: Inventory UI Page
dependents: []
priority: 5
tags: [ui, inventory, equipment, iss-074]
parents:
  - [[req_ui_look_and_feel]]
  - [[ui_theme]]
  - [[upsilonapi:api_equipment_management]]
  - [[upsilonapi:api_inventory_list]]
---

# New Atom

## INTENT
To provide an inventory page where players see what they own and bind items to specific characters via the equip flow. Read-side mirrors `[[api_inventory_list]]`; write-side calls `[[api_equipment_management]]` equip / unequip endpoints.

## THE RULE / LOGIC
**Route:** `/inventory` (Inertia route).

**Componentisation by responsibility:**
- `Components/Inventory/InventoryRow.vue` — single inventory entry: name, qty, slot icon, equipped-on (character name or "—"). Emits `@equip`, `@unequip`.
- `Components/Inventory/InventoryTabs.vue` — slot tabs (All / Armor / Utility / Weapon). Props `{ activeTab }`. Emits `@change`.
- `Components/Inventory/InventoryList.vue` — composes `InventoryTabs` + filtered `InventoryRow`s.
- `Components/Inventory/EquipDrawer.vue` — slide-out: shows character + 3 slots + compatible items, equip/unequip buttons. Self-contained equip flow; can be opened from `Pages/Inventory.vue` (item-first) or from `EquipmentSlotPill.vue` on the dashboard (character-first).
- `Pages/Inventory.vue` — composes `InventoryList` + `EquipDrawer`, calls `services/inventory.js`.

**Service layer:** `resources/js/services/inventory.js` — `listInventory()`, `getEquipment(charId)`, `equip(charId, itemId)`, `unequip(charId, slot)`.

**Theme compliance:** Same panel / border / corner / typography rules as `[[ui_shop]]`. Slot tabs styled as HUD readout with active-state pulse on selected tab.

**Behavior:**
- Tab `All` shows everything; slot-specific tabs filter by `shop_item.slot`.
- Each row's "Equipped on" badge links into the dashboard character card.
- Equipping an item already bound to another character shows an inline confirmation: "Detach from {prev_character}?" → confirms cross-character mutual exclusivity transfer.

## TECHNICAL INTERFACE
- **Code Tag:** `@spec-link [[ui_inventory]]`
- **Page:** `resources/js/Pages/Inventory.vue`
- **Components:** `resources/js/Components/Inventory/*.vue`
- **Service:** `resources/js/services/inventory.js`

## EXPECTATION
- Browsing `/inventory` shows owned items with correct equip annotations.
- Tab switching filters in-place without an API roundtrip.
- Equipping an item from inventory updates both the inventory page and the character dashboard simultaneously.
- Cross-character equip shows the confirmation dialog and atomically transfers.
