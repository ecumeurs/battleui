---
id: ui_character_full_stat_panel
status: DRAFT
priority: 5
type: UI
tags: [ui, character, stats, dashboard, iss-074]
human_name: Character Full Stat Panel UI
parents:
  - [[req_ui_look_and_feel]]
  - [[shared:rule_progression]]
  - [[shared:rule_stat_taxonomy]]
  - [[ui_dashboard]]
  - [[ui_theme]]
dependents:
  - [[ui_diagnostic_terminal]]
layer: ARCHITECTURE
version: 2.0
---

# New Atom

## INTENT
To replace the legacy V1 "level" character display with a complete V2 stat panel: 9 Class A stats (CP-upgradable) + 2 Class B stats (item-only) + CP economy summary + equipment contribution column. The single canonical stat surface for characters on the dashboard.

## THE RULE / LOGIC
**Componentisation by responsibility:**
- `Components/Character/StatRow.vue` — one stat row. Props `{ label, base, contributions, effective, cpCost, classB, max? }`. Renders: label, base value, contribution badges (per source), effective value. CP cost shown as a click-target only when `cpCost` is provided (Class A only). `max` rendered as `current/max` when supplied (counter stats: HP, MP, SP, Shield).
- `Components/Character/CharacterStatPanel.vue` — composes 11 `StatRow` instances in two visual groupings: Class A (HP, MP, SP, Attack, Defense, Movement, JumpHeight, CritChance, CritDamage) and Class B (AttackRange, Shield). Class B rows show "Item-granted only" instead of a CP cost.
- `Components/Character/CpEconomySummary.vue` — `{ spent, max }` bar showing `spent_cp / (100 + total_wins*10)` per `[[rule_progression]]`.
- `Components/Character/CharacterCard.vue` — top-level character composer: name + `CharacterStatPanel` + `CpEconomySummary` + `CharacterEquipmentPanel`. Replaces the inline character markup currently in `CharacterRoster.vue`.
- `composables/useCharacterStats.js` — pure function `(character, equipment) → { class_a_rows, class_b_rows, contribution_breakdown }`. Keeps components presentational.

**Replaces:** the broken V1 level math at `CharacterRoster.vue:154` (`hp + attack + defense + movement - 9`) which is meaningless against V2 baselines.

**Equipment contribution column:** For each stat, sum the equipped items' properties affecting that stat. Examples:
- Armor with `ArmorRating:5` → contribution row under Defense (or as its own row, design call: render Armor as its own row to mirror the engine model).
- Sword with `WeaponBaseDamage:5` → contribution row under Attack.
- Boots with `Movement:1` → contribution row under Movement.

**Tooltip on hover:** Stat breakdown — `base + equipment + (battle-time buffs)`.

**Theme:** Standard gunmetal-cyan panel + Orbitron uppercase title.

## TECHNICAL INTERFACE
- **Code Tag:** `@spec-link [[ui_character_full_stat_panel]]`
- **Components:** `resources/js/Components/Character/StatRow.vue`, `CharacterStatPanel.vue`, `CpEconomySummary.vue`, `CharacterCard.vue`
- **Composable:** `resources/js/composables/useCharacterStats.js`
- **Replaces inline markup in:** `resources/js/Components/CharacterRoster.vue`

## EXPECTATION
- Character with no items shows 11 stats; Class B rows are labelled "Item-granted only".
- Equipping a basic sword adds a `+5 WeaponBaseDamage` contribution row visible from the dashboard.
- Unequipping reverts the contribution.
- The CP economy bar reflects `spent_cp / (100 + total_wins * 10)` exactly.
- The legacy V1 "level" calculation is removed.
