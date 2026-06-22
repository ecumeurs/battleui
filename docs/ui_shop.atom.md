---
id: ui_shop
status: DRAFT
version: 2.0
priority: 5
human_name: Shop UI Page
parents:
  - [[req_ui_look_and_feel]]
  - [[ui_theme]]
  - [[upsilonapi:api_shop_browse]]
  - [[upsilonapi:api_shop_purchase]]
dependents: []
layer: ARCHITECTURE
tags: [ui, shop, dashboard, iss-074]
type: UI
---

# Shop UI Page

## INTENT
To provide a dedicated shop page where players browse the V2.0 catalog and purchase items with credits, surfaced via the "Neon in the Dust" aesthetic with sci-fi / post-apoc terminology.

## THE RULE / LOGIC
**Route:** `/shop` (Inertia route registered in `routes/web.php`).

**Componentisation by responsibility (per CLAUDE.md cross-cutting principle):**
- `Components/Shop/ShopItemCard.vue` — single shop entry: name, slot icon, properties summary, cost, action button. Props `{ item, ownedQty, canAfford }`. Emits `@purchase`.
- `Components/Shop/ShopGrid.vue` — composes a v-for of `ShopItemCard`, handles loading state.
- `Components/Shop/PurchaseConfirmModal.vue` — wraps the existing `ConfirmModal` with shop-specific copy ("Acquire {item}? -{cost} credits").
- `Pages/Shop.vue` — composes `ShopGrid` + `PurchaseConfirmModal`, calls `services/shop.js`, optimistically decrements credits on success.

**Service layer:** `resources/js/services/shop.js` — `listItems()`, `purchase(itemId, qty=1)`. Mirrors `services/game.js` envelope handling (auth axios, response interceptor unwraps).

**Theme compliance (per `[[req_ui_look_and_feel]]` + `[[ui_theme]]`):**
- Panels: `bg-upsilon-gunmetal/30` + `backdrop-blur-md`, 1px cyan/30 or magenta/30 border, 2px corner accents (`border-t-2 border-l-2`).
- Titles: Orbitron `uppercase tracking-[0.3em]`.
- Hover: increase border-opacity + `shadow-glow-cyan`.
- Copy: post-apoc / sci-fi terminology ("ACQUIRE", "INVENTORY CACHE", "CREDIT LINK").

**Affordability:**
- Disabled "Acquire" button + tooltip "Insufficient credits" when `user.credits < item.cost`.
- Optimistic credit decrement on successful purchase, with rollback if the API returns an error.

**Navigation:** Link added to `Components/TacticalHeader.vue` ("Shop" or sci-fi equivalent).

## TECHNICAL INTERFACE
- **Code Tag:** `@spec-link [[ui_shop]]`
- **Page:** `resources/js/Pages/Shop.vue`
- **Components:** `resources/js/Components/Shop/*.vue`
- **Service:** `resources/js/services/shop.js`

## EXPECTATION
- Browsing `/shop` shows three V2.0 catalog cards.
- Insufficient credits disables the purchase button with a clear tooltip.
- A successful purchase decrements the credit balance shown in `IdentitySection` and increments inventory.
- All panels render with cyan/magenta-bordered gunmetal background and Orbitron titles.
