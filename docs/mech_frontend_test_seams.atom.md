---
id: mech_frontend_test_seams
status: DRAFT
type: MECHANIC
layer: IMPLEMENTATION
version: 1.0
tags: [testing, playwright, debug, frontend, component-isolation]
human_name: Frontend Test Seams (Component Viewer + Debug Hook)
priority: 3
parents:
  - [[ui_iso_board]]
dependents: []
---

# Frontend Test Seams

## INTENT
To provide stable, machine-readable hooks that let Playwright (and human reviewers) inspect and screenshot 3D components in isolation, without needing a live match or authentication.

## THE RULE / LOGIC

### `window.__upsilonDebug` contract
Fixture pages (`/__test/component/{name}`) set this object on the browser `window` after the canvas mounts:

```ts
window.__upsilonDebug = {
  ready: true,       // canvas mounted and Vue component settled
  frozen: true,      // animation loop is not running (static frame)
  version: 1,        // contract version — bump if shape changes
  fixture: string,   // the fixture name from the URL
}
```

Playwright Layer 2 tests wait with:
```ts
await page.waitForFunction(() => window.__upsilonDebug?.frozen === true)
```

**Invariant:** `frozen` must only be `true` when the canvas has rendered at least one frame. A page that sets `frozen` before the TresCanvas mounts will cause Playwright to snapshot a blank screen.

### `/__test/component` route family
- **No authentication middleware** — accessible from fresh browser / curl without a session.
- **No Laravel env guard** — the `/__test/component` path prefix is the production gate by convention. Do not add features to these pages that would be harmful if publicly reachable; they render only static fixture data with no DB reads.
- Routes:
    - `GET /__test/component/` → `TestComponentIndex` Inertia page (fixture gallery)
    - `GET /__test/component/{name}` → `TestComponent` Inertia page (isolated 3D viewer)
- Fixture names are the source of truth for Playwright test IDs. Renaming a fixture = renaming the baseline PNG = intentional snapshot update.

### Fixture naming convention
`{component}-{variant}`, e.g.:
- `grid-flat`, `grid-elevated`, `grid-obstacles`
- `grid-pawn-self`, `grid-pawn-blue`, `grid-pawn-red`, `grid-pawn-active`
- `grid-highlight-move`, `grid-highlight-attack`
- `grid-full`

Adding a new `Components/Arena/Three*.vue` requires a corresponding fixture in `TestComponent.vue` in the same commit.

## TECHNICAL INTERFACE
- **Code Tag:** `@spec-link [[mech_frontend_test_seams]]`
- **Route registration:** `battleui/routes/web.php` (dev-only guard)
- **Fixture gallery:** `battleui/resources/js/Pages/TestComponentIndex.vue`
- **Fixture viewer:** `battleui/resources/js/Pages/TestComponent.vue`
- **Playwright config:** `battleui/playwright.config.ts`
- **Snapshot baseline dir:** `battleui/tests/playwright/__snapshots__/` (committed)

## EXPECTATION
- `GET /__test/component/` returns 200 without a session cookie in any environment.
- Each fixture page sets `window.__upsilonDebug.frozen === true` before Playwright can proceed.
- An unknown fixture name renders an error panel, not a blank screen.
