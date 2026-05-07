---
id: ui_theme
status: STABLE
human_name: UI Theme Specification
dependents:
  - [[ui_character_equipment_panel]]
  - [[ui_character_full_stat_panel]]
  - [[ui_inventory]]
  - [[ui_shop]]
  - [[ui_skill_icon]]
type: UI
layer: ARCHITECTURE
version: 2.0
priority: 5
tags: [ui, styling, theme, tokens]
parents:
  - [[req_ui_look_and_feel]]
---

# UI Theme Specification

## INTENT
Single source of truth for all design tokens (colors, font sizes) used across the application. Every component must reference these tokens rather than hardcoding hex values or pixel sizes.

## THE RULE / LOGIC

### Token Hierarchy

Three layers, each consuming from the one below:

| Layer | File | Used by |
|---|---|---|
| **CSS custom properties** | `resources/css/app.css` `:root` | `<style scoped>` blocks |
| **Tailwind config tokens** | `tailwind.config.js` | Tailwind utility classes in templates |
| **JS constants** | `resources/js/constants/theme.js` | Component `<script>` logic (computed colors) |

### Font Size Scale

Only four sizes are permitted for UI text. Never use raw pixel values.

| Token | CSS var | Tailwind class | Size |
|---|---|---|---|
| xs | `var(--fs-xs)` | `text-ui-xs` | 11px |
| sm | `var(--fs-sm)` | `text-ui-sm` | 13px |
| md | `var(--fs-md)` | `text-ui-md` | 15px |
| lg | `var(--fs-lg)` | `text-ui-lg` | 17px |

Large display text (game-over headlines, stat numbers) may use larger sizes but must be explicit and intentional, not incidental.

### Color Palette

#### Base palette (structural)
- **Deep Void** `#0a0a0b` — `upsilon.void` — page/panel backgrounds
- **Gunmetal** `#1a1a1e` — `upsilon.gunmetal` — secondary surfaces
- **Oxidized Iron** `#3d2b1f` — `upsilon.rust` — obstacle/hazard surfaces
- **Worn Steel** `#4a4a4f` — `upsilon.steel` — borders and dividers

#### Accent palette
- **Cyan** `#00f2ff` — `var(--color-cyan)` / `upsilon.cyan` — primary accent, move zone
- **Magenta** `#ff00ff` — `var(--color-magenta)` / `upsilon.magenta` — secondary accent, attack UI
- **Lime** `#39ff13` — `var(--color-lime)` / `upsilon.lime` — ally/HP healthy
- **Blue** `#00a8ff` — `var(--color-blue)` — current player identity
- **Red** `#ff2020` — `var(--color-red)` — danger, enemies, attack zone
- **Gold** `#fbbf24` — `var(--color-gold)` — skill zone, sandbox HUD
- **Purple** `#b030ff` — `var(--color-purple)` — secondary enemy
- **Orange** `#ff8c00` — `var(--color-orange)` — HP warning, shot clock

#### Semantic combat tokens

**Zone colors** (3D board highlights — `ZONE_COLORS` in theme.js):
- Move: `var(--color-zone-move)` = `#00f2ff`
- Attack: `var(--color-zone-attack)` = `#ff2020`
- Skill: `var(--color-zone-skill)` = `#fbbf24`

**Team identity colors** (roster, timeline — `TEAM_COLORS` in theme.js):
- Self (current player): `var(--color-team-self)` = `#00a8ff`
- Ally (teammate): `var(--color-team-ally)` = `#39ff13`
- Enemy (primary foe): `var(--color-team-enemy)` = `#ff2020`
- Enemy2 (secondary foe): `var(--color-team-enemy2)` = `#b030ff`

**Pawn colors** (3D board entities — `PAWN_COLORS` in theme.js):
- Player's own entity: `#39ff13` (lime)
- Allied entity: `#00a8ff` (blue)
- Enemy entity: `#ff2020` (red)

**HP bar states** (computed by `hpColor(hp, maxHp)` in theme.js):
- `> 60%` → `var(--color-hp-healthy)` = `#39ff13`
- `> 30%` → `var(--color-hp-warning)` = `#ff8c00`
- `≤ 30%` → `var(--color-hp-critical)` = `#ff2020`

### Typography
- **Headings/Labels**: `Orbitron` — `font-scifi`
- **Body**: `Inter` — `font-sans`
- **Technical/Readouts**: `JetBrains Mono` — `font-mono`
- Standard styling: `uppercase` + `tracking-[0.3em]` for HUD-style labels.

### Readability Rule
Text elements must never use base colors (`void`, `gunmetal`, `rust`, `steel`) on dark backgrounds. Use `var(--color-text)` (`#e0e0e0`) for neutral body text.

## TECHNICAL INTERFACE
- **Code Tag:** `@spec-link [[ui_theme]]`
- **JS source of truth:** [resources/js/constants/theme.js](file:///workspace/battleui/resources/js/constants/theme.js)
- **CSS custom properties:** [resources/css/app.css](file:///workspace/battleui/resources/css/app.css)
- **Tailwind config:** [tailwind.config.js](file:///workspace/battleui/tailwind.config.js)

## EXPECTATION
- All `<style scoped>` font-size values must use `var(--fs-xs/sm/md/lg)`.
- All semantic colors in `<style scoped>` must use `var(--color-*)` tokens.
- All JS computed color logic must import from `constants/theme.js` — no inline hex.
- Tailwind classes (`text-ui-xs`, `text-team-self`, `bg-zone-attack`, etc.) are preferred over CSS vars in template HTML.
- The primary display font for all HUD labels is Orbitron.
