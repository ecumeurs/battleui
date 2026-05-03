---
id: ui_skill_icon
human_name: Skill Icon Component
type: UI
layer: ARCHITECTURE
version: 1.0
status: DRAFT
priority: 4
tags: [ui, skills, icons, neon, polygons]
parents:
  - [[req_ui_look_and_feel]]
  - [[shared:req_skill_generation_overhaul]]
  - [[ui_theme]]
dependents: []
---

# Skill Icon Component

## INTENT
To render a composable neon-polygon icon for any skill based on its ordered tag list, following the "Neon in the Dust" aesthetic. The major glyph represents the primary tag; minor overlays represent secondary tags.

## THE RULE / LOGIC

**Glyph vocabulary** — each tag maps to an SVG path (stroked only, 1.5px, neon drop-shadow):

| Tag | Glyph | Color |
|---|---|---|
| `melee` | Crossed daggers (×) | magenta `#ff00ff` |
| `ranged` | Chevron / arrow (▶) | cyan `#00f2ff` |
| `aoe` | Concentric hex rings | cyan `#00f2ff` |
| `heal` | Plus cross (+) | lime `#39ff13` |
| `shield` | Hexagon outline | cyan `#00f2ff` |
| `buff` | Upward triangle (△) | lime `#39ff13` |
| `debuff` | Downward triangle (▽) | magenta `#ff00ff` |
| `dot` | Dripping rhombus | lime `#39ff13` |
| `stun` | Zigzag bolt | amber `#fbbf24` |
| `crit` | Star / asterisk | amber `#fbbf24` |
| `trap` | Trapezoid mine | amber `#fbbf24` |
| `counter` | Mirrored arrows (⇄) | magenta `#ff00ff` |
| `reaction` | Broken arrow | magenta `#ff00ff` |
| `passive` | Infinity loop (∞) | steel `#4a4a4f` |
| `mobility` | Sprint chevrons (»›) | cyan `#00f2ff` |
| `channeled` | Hourglass | steel `#4a4a4f` |
| `instant` | Lightning chevron | cyan `#00f2ff` |

**Composition:**
- Major glyph (tags[0]) — full size, 100% opacity, strong glow.
- Minor glyph (tags[1]) — 14×14, top-right corner, 70% opacity, dimmer glow.
- Tertiary glyph (tags[2], grade ≥ III only) — 10×10, bottom-right, 50% opacity.
- Background ring: thin hex border colored by grade (I=steel, II=cyan, III=lime, IV=magenta, V=amber).

**Usage context sizes:**
- Action slot button: 32×32
- Detail panel / roulette reveal: 64×64
- Passive rail / slot pill: 20×20

## TECHNICAL INTERFACE
- **Code Tag:** `@spec-link [[ui_skill_icon]]`
- **Component:** `battleui/resources/js/Components/Skill/SkillIcon.vue`
  - Props: `tags: string[]`, `grade: string`
- **Registry:** `battleui/resources/js/Components/Skill/skillIconRegistry.js`
  - Maps tag string → inline SVG `<path>` data. No asset pipeline.
- **Consumers:** `SkillCard.vue`, `SkillDetail.vue`, `SkillSlotPill.vue`, `ActionPanel.vue`

## EXPECTATION
- Icon renders without a missing-glyph fallback for all 17 known tags.
- Major glyph is visually dominant; overlay glyphs do not occlude it.
- Grade color ring is correct at each grade level.
- Unknown tags render a neutral `?` glyph (steel color) without throwing an error.
- No external image assets required — all paths are inline SVG strings.
