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

# Character Full Stat Panel UI

## INTENT
To replace the legacy V1 "level" character display with a complete V2 stat panel: 9 Class A stats (CP-upgradable) + 2 Class B stats (item-only) + CP economy summary + equipment contribution column. The single canonical stat surface for characters on the dashboard.

## THE RULE / LOGIC
**Componentisation by responsibility:**
- `Components/Character/StatRow.vue` — one stat row. Props `{ label, base, contributions, effective, cpCost, classB, max? }`. Renders: label, base value, contribution badges (per source), effective value. CP cost shown as a click-target only when `cpCost` is provided (Class A only). `max` rendered as `current/max` when supplied (counter stats: HP, MP, SP, Shield).
- `Components/Character/CharacterStatPanel.vue` — composes 11 `StatRow` instances in two visual groupings: Class A (HP, MP, SP, Attack, Defense, Movement, JumpHeight, CritChance, CritDamage) and Class B (AttackRange, Shield). Class B rows show "Item-granted only" instead of a CP cost.
- `Components/Character/CpEconomySummary.vue` — `{ spent, max }` bar showing `spent_cp / (100 + total_wins*10)` per `[[shared:rule_progression]]`.
- `Components/Character/CharacterCard.vue` — top-level character composer: name + `CharacterStatPanel` + `CpEconomySummary` + `CharacterEquipmentPanel`. Replaces the inline character markup currently in `CharacterRoster.vue`.
- `composables/useCharacterStats.js` — pure function `(character, equipment) → { class_a_rows, class_b_rows, contribution_breakdown }`. Keeps components presentational.

**Replaces:** the broken V1 level math at `CharacterRoster.vue:154` (`hp + attack + defense + movement - 9`) which is meaningless against V2 baselines.

**Equipment contribution column:** For each stat, sum the equipped items' properties affecting that stat. Examples:
- Armor with `ArmorRating:5` → contribution row under Defense (or as its own row, design call: render Armor as its own row to mirror the engine model).
- Sword with `WeaponBaseDamage:5` → contribution row under Attack.
- Boots with `Movement:1` → contribution row under Movement.

**Identity & Economy header:** Displays character name, current level, total wins, and real-time credit balance alongside the CP economy bar (base 100 + win-based accumulation).

**Exotic / Class B attributes (extended set):** Beyond the core Class A/B rows, surface secondary tactical stats where present: Critical Chance, Critical Damage/Multiplier, Dodge/Evasion, Accuracy, Jump Height, Attack Range, Backstab Multiplier, and Armor Penetration.

**Skill Repository:** Lists all owned skills with Grade, Skill Weight (SW), resource costs (MP/SP), and current cooldown timers. Detailed modals provide the precise damage/effect formula and a targeting preview on the grid.

**Equipment management:** A 3-slot interface (Armor, Utility, Weapon) with rarity-tier colour coding (Common/Uncommon/Rare...). Unequipped inventory items are shown for swapping with stat-comparison tooltips; a comparison engine renders high-contrast green/red indicators for projected stat deltas vs. the currently equipped item.

**Progression history:** XP progress bar toward the next level (with upcoming reward/skill-unlock markers) and a log of recent stat changes / transaction history for transparency.

**Performance:** Progressive data loading and client-side caching keep the panel responsive under high-frequency attribute updates.

**Theme:** Standard gunmetal-cyan panel + Orbitron uppercase title.

## TECHNICAL INTERFACE
- **Code Tag:** `@spec-link [[ui_character_full_stat_panel]]`
- **Components:** `resources/js/Components/Character/StatRow.vue`, `CharacterStatPanel.vue`, `CpEconomySummary.vue`, `CharacterCard.vue`
- **Composable:** `resources/js/composables/useCharacterStats.js`
- **Replaces inline markup in:** `resources/js/Components/CharacterRoster.vue`

## EXPECTATION
- Character with no items shows the 11 base stats; Class B rows are labelled "Item-granted only".
- Equipping a basic sword adds a `+5 WeaponBaseDamage` contribution row visible from the dashboard; unequipping reverts the contribution.
- The CP economy bar reflects `spent_cp / (100 + total_wins * 10)` exactly, and the header shows name, level, total wins, and credit balance.
- Owned skills list shows Grade, Skill Weight, MP/SP cost, and cooldown; opening a skill modal shows its formula and grid targeting preview.
- The 3 equipment slots (Armor/Utility/Weapon) render rarity colour coding, and comparing an inventory item against an equipped one shows green/red projected stat deltas.
- The legacy V1 "level" calculation is removed.
