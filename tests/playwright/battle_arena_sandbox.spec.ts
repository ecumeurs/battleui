// @test-link [[mech_frontend_test_seams]]
// Battle Arena Sandbox — Playwright tests for arena logic & UI.
// Tests validate: HP bar sync, character death, movement, skill targeting display,
// turn transitions, game-over overlays, keyboard shortcuts, zone highlights, hover disc, and visual snapshots.
import { test, expect } from '@playwright/test';

const BASE = '/__test/battle';

// Helper: navigate to scenario and wait for sandbox ready
async function loadScenario(page: any, scenario: string, step = 0) {
    const url = step > 0 ? `${BASE}/${scenario}?step=${step}` : `${BASE}/${scenario}`;
    await page.goto(url);
    await page.waitForFunction(() => (window as any).__upsilonDebug?.ready === true);
}

// Helper: advance to next step via __upsilonDebug.nextStep()
async function nextStep(page: any) {
    await page.evaluate(() => (window as any).__upsilonDebug.nextStep());
    // Brief wait for Vue to re-render
    await page.waitForTimeout(120);
}

// Helper: wait until SceneInspector has mounted and populated __upsilonDebug.board
async function waitForBoard(page: any) {
    await page.waitForFunction(
        () => (window as any).__upsilonDebug?.board !== null,
        { timeout: 5000 },
    );
}

// Helper: get screen position of a highlighted cell via camera projection
async function getZoneCellScreenPos(page: any, cellIndex = 0): Promise<{ x: number; y: number } | null> {
    return page.evaluate((idx: number) => {
        const board = (window as any).__upsilonDebug?.board;
        if (!board?.highlightedCells?.length) return null;
        const cell = board.highlightedCells[idx] ?? board.highlightedCells[0];
        return board.getCellScreenPos(cell.x, cell.y);
    }, cellIndex);
}

// ─── Index ───────────────────────────────────────────────────────────────────

test('sandbox index lists all scenarios', async ({ page }) => {
    await page.goto(BASE);
    await expect(page.locator('.terminal__title')).toContainText('BATTLE ARENA SANDBOX');
    const cards = page.locator('a[href^="/__test/battle/"]');
    await expect(cards.first()).toBeVisible();
    // At least damage-hp-update and game-end-win should be present
    await expect(page.locator(`a[href="${BASE}/damage-hp-update"]`)).toBeVisible();
    await expect(page.locator(`a[href="${BASE}/game-end-win"]`)).toBeVisible();
});

test('unknown scenario shows error message', async ({ page }) => {
    await page.goto(`${BASE}/nonexistent-scenario-xyz`);
    await expect(page.locator('text=Unknown scenario')).toBeVisible();
});

// ─── HP bar sync ─────────────────────────────────────────────────────────────

test('HP bar aria-valuenow decreases after damage step', async ({ page }) => {
    await loadScenario(page, 'damage-hp-update');

    // Step 0: Kairo at full HP (80)
    const hpBar = page.locator('[data-testid="roster-card"][data-entity-id="e1"] [role="progressbar"]').first();
    const initialHp = await hpBar.getAttribute('aria-valuenow');
    expect(parseInt(initialHp ?? '0')).toBe(80);

    await nextStep(page);

    // Step 1: Kairo HP dropped to 50
    const updatedHp = await hpBar.getAttribute('aria-valuenow');
    expect(parseInt(updatedHp ?? '999')).toBe(50);
});

test('enemy team HP decreases in CombatHeader after damage', async ({ page }) => {
    await loadScenario(page, 'damage-hp-update');

    const allyHp = page.locator('[data-testid="team-hp-ally"]');
    const initial = await allyHp.getAttribute('aria-valuenow');

    await nextStep(page);

    const updated = await allyHp.getAttribute('aria-valuenow');
    // Ally HP should have decreased (Kairo was hit)
    expect(parseInt(updated ?? '9999')).toBeLessThan(parseInt(initial ?? '0'));
});

// ─── Character death ──────────────────────────────────────────────────────────

test('dead entity is removed from initiative timeline', async ({ page }) => {
    await loadScenario(page, 'character-death');

    // Step 0: enemy (e3) is in timeline
    await expect(page.locator('[data-testid="turn-token"][data-entity-id="e3"]')).toBeVisible();

    await nextStep(page);

    // Step 1: e3 dead — removed from turn order array, token should not exist
    await expect(page.locator('[data-testid="turn-token"][data-entity-id="e3"]')).toHaveCount(0);
});

// ─── Movement ────────────────────────────────────────────────────────────────

test('roster card still present after movement (entity not lost)', async ({ page }) => {
    await loadScenario(page, 'movement-sequence');

    await expect(page.locator('[data-testid="roster-card"][data-entity-id="e1"]')).toBeVisible();

    await nextStep(page);

    // Entity should still be in roster after moving
    await expect(page.locator('[data-testid="roster-card"][data-entity-id="e1"]')).toBeVisible();
});

// ─── Skill variants in ActionPanel ───────────────────────────────────────────

test('passive skill appears in panel with correct behavior attr', async ({ page }) => {
    await loadScenario(page, 'skill-passive-display');
    // Passive skills render in the passive rail (not as clickable buttons)
    // They do NOT get data-testid="skill-btn" — they use class ap-passive
    const passiveRail = page.locator('.ap-passive');
    await expect(passiveRail).toBeVisible();
});

test('direct skill button has behavior=Direct', async ({ page }) => {
    await loadScenario(page, 'skill-direct-range');
    const skillBtn = page.locator('[data-testid="skill-btn"][data-behavior="Direct"]');
    await expect(skillBtn).toBeVisible();
});

test('reactive and counter skills appear in panel', async ({ page }) => {
    await loadScenario(page, 'skill-reactive-display');
    await expect(page.locator('[data-testid="skill-btn"][data-behavior="Reaction"]')).toBeVisible();
    await expect(page.locator('[data-testid="skill-btn"][data-behavior="Counter"]')).toBeVisible();
});

test('trap skill button has behavior=Trap', async ({ page }) => {
    await loadScenario(page, 'skill-trap');
    await expect(page.locator('[data-testid="skill-btn"][data-behavior="Trap"]')).toBeVisible();
});

// ─── Turn transition ─────────────────────────────────────────────────────────

test('action panel status changes after turn transition', async ({ page }) => {
    await loadScenario(page, 'turn-transition');

    // Step 0: e1 (self) is active → YOUR TURN
    const status = page.locator('.ap-status__text');
    await expect(status).toContainText('YOUR TURN');

    await nextStep(page);

    // Step 1: e3 (enemy) is active → WAITING
    await expect(status).toContainText('WAITING');
});

test('initiative timeline active token shifts after turn transition', async ({ page }) => {
    await loadScenario(page, 'turn-transition');

    await expect(page.locator('[data-testid="turn-token"][data-entity-id="e1"].timeline__token--active')).toBeVisible();

    await nextStep(page);

    await expect(page.locator('[data-testid="turn-token"][data-entity-id="e3"].timeline__token--active')).toBeVisible();
    await expect(page.locator('[data-testid="turn-token"][data-entity-id="e1"].timeline__token--active')).toHaveCount(0);
});

// ─── Game-over overlays ───────────────────────────────────────────────────────

test('victory overlay shown on game-end-win scenario', async ({ page }) => {
    await loadScenario(page, 'game-end-win');
    await expect(page.locator('.game-over--victory')).toBeVisible();
    await expect(page.locator('.game-over__title')).toContainText('VICTORY');
});

test('defeat overlay shown on game-end-loss scenario', async ({ page }) => {
    await loadScenario(page, 'game-end-loss');
    await expect(page.locator('.game-over--defeat')).toBeVisible();
    await expect(page.locator('.game-over__title')).toContainText('DEFEAT');
});

// ─── Keyboard shortcuts ───────────────────────────────────────────────────────

test('pressing M selects move action (move button gets selected state)', async ({ page }) => {
    await loadScenario(page, 'damage-hp-update'); // self is active on step 0
    await page.keyboard.press('m');
    await expect(page.locator('[data-testid="action-btn-move"].ap-btn--selected')).toBeVisible();
});

test('pressing Escape cancels move selection', async ({ page }) => {
    await loadScenario(page, 'damage-hp-update');
    await page.keyboard.press('m');
    await expect(page.locator('[data-testid="action-btn-move"].ap-btn--selected')).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.locator('[data-testid="action-btn-move"].ap-btn--selected')).toHaveCount(0);
});

test('pressing A selects attack action', async ({ page }) => {
    await loadScenario(page, 'damage-hp-update');
    await page.keyboard.press('a');
    await expect(page.locator('[data-testid="action-btn-attack"].ap-btn--selected')).toBeVisible();
});

// ─── Skill targeting: entering mode shows gold highlights ────────────────────

test('clicking direct skill button highlights valid target tiles', async ({ page }) => {
    await loadScenario(page, 'skill-direct-range');
    const skillBtn = page.locator('[data-testid="skill-btn"][data-behavior="Direct"]');
    await skillBtn.click();

    // After clicking the skill, selectedAction should be a skill object
    // The GridHighlight renders gold tiles (skill type). Canvas is 3D so we
    // verify via the debug state that the action mode is skill targeting.
    const actionMode = await page.evaluate(() => {
        const debug = (window as any).__upsilonDebug;
        return debug?.getState?.();
    });
    expect(actionMode).toBeTruthy();
    // The skill button should now have selected state
    await expect(skillBtn).toHaveClass(/ap-btn--selected/);
});

test('clicking trap skill button enters targeting mode', async ({ page }) => {
    await loadScenario(page, 'skill-trap');
    const trapBtn = page.locator('[data-testid="skill-btn"][data-behavior="Trap"]');
    await trapBtn.click();
    await expect(trapBtn).toHaveClass(/ap-btn--selected/);
});

// ─── Animation markers ────────────────────────────────────────────────────────

test('anim-attack marker present briefly after stepping into attack state', async ({ page }) => {
    await loadScenario(page, 'damage-hp-update');
    await nextStep(page);
    // animAction fires on step transition when action.type === 'attack'
    // The marker div should appear and disappear within 800ms
    const marker = page.locator('[data-testid="anim-attack"]');
    await expect(marker).toBeAttached({ timeout: 500 });
    // After 900ms it should be gone
    await page.waitForTimeout(900);
    await expect(marker).toHaveCount(0);
});

// ─── Console error guard ─────────────────────────────────────────────────────

test('no console errors on scenario load', async ({ page }) => {
    const errors: string[] = [];
    page.on('console', msg => { if (msg.type() === 'error') errors.push(msg.text()); });

    const scenarios = ['damage-hp-update', 'movement-sequence', 'skill-direct-range', 'turn-transition', 'game-end-win'];
    for (const name of scenarios) {
        errors.length = 0;
        await loadScenario(page, name);
        expect(errors, `Console errors in "${name}": ${errors.join(' | ')}`).toHaveLength(0);
    }
});

// ─── Zone presence + HoverDisc (via SceneInspector / __upsilonDebug.board) ───

test('move zone: cells present in board state after clicking MOVE', async ({ page }) => {
    await loadScenario(page, 'movement-sequence');
    await waitForBoard(page);

    await page.locator('[data-testid="action-btn-move"]').click();
    await page.waitForTimeout(100);

    const cells: any[] = await page.evaluate(() => (window as any).__upsilonDebug.board.highlightedCells);
    expect(cells.length).toBeGreaterThan(0);
    expect(cells.every((c: any) => c.type === 'move')).toBe(true);
});

test('move zone: HoverDisc activates when mouse enters a valid cell', async ({ page }) => {
    await loadScenario(page, 'movement-sequence');
    await waitForBoard(page);

    await page.locator('[data-testid="action-btn-move"]').click();
    await page.waitForTimeout(100);

    const pos = await getZoneCellScreenPos(page);
    if (!pos) { test.skip(); return; }

    await page.mouse.move(pos.x, pos.y);
    await page.waitForTimeout(120);

    const hovered = await page.evaluate(() => (window as any).__upsilonDebug.board.hoveredCell);
    expect(hovered).not.toBeNull();
});

test('attack zone: cells present in board state after clicking ATTACK', async ({ page }) => {
    await loadScenario(page, 'damage-hp-update');
    await waitForBoard(page);

    await page.locator('[data-testid="action-btn-attack"]').click();
    await page.waitForTimeout(100);

    const cells: any[] = await page.evaluate(() => (window as any).__upsilonDebug.board.highlightedCells);
    expect(cells.length).toBeGreaterThan(0);
    expect(cells.every((c: any) => c.type === 'attack')).toBe(true);
});

test('attack zone: HoverDisc activates when mouse enters a valid cell', async ({ page }) => {
    await loadScenario(page, 'damage-hp-update');
    await waitForBoard(page);

    await page.locator('[data-testid="action-btn-attack"]').click();
    await page.waitForTimeout(100);

    const pos = await getZoneCellScreenPos(page);
    if (!pos) { test.skip(); return; }

    await page.mouse.move(pos.x, pos.y);
    await page.waitForTimeout(120);

    const hovered = await page.evaluate(() => (window as any).__upsilonDebug.board.hoveredCell);
    expect(hovered).not.toBeNull();
});

test('skill zone: cells present in board state after clicking a Direct skill', async ({ page }) => {
    await loadScenario(page, 'skill-direct-range');
    await waitForBoard(page);

    await page.locator('[data-testid="skill-btn"][data-behavior="Direct"]').click();
    await page.waitForTimeout(100);

    const cells: any[] = await page.evaluate(() => (window as any).__upsilonDebug.board.highlightedCells);
    expect(cells.length).toBeGreaterThan(0);
    expect(cells.every((c: any) => c.type === 'skill')).toBe(true);
});

test('skill zone: HoverDisc activates when mouse enters a valid cell', async ({ page }) => {
    await loadScenario(page, 'skill-direct-range');
    await waitForBoard(page);

    await page.locator('[data-testid="skill-btn"][data-behavior="Direct"]').click();
    await page.waitForTimeout(100);

    const pos = await getZoneCellScreenPos(page);
    if (!pos) { test.skip(); return; }

    await page.mouse.move(pos.x, pos.y);
    await page.waitForTimeout(120);

    const hovered = await page.evaluate(() => (window as any).__upsilonDebug.board.hoveredCell);
    expect(hovered).not.toBeNull();
});

test('Escape clears zone and board returns to empty state', async ({ page }) => {
    await loadScenario(page, 'movement-sequence');
    await waitForBoard(page);

    await page.locator('[data-testid="action-btn-move"]').click();
    await page.waitForTimeout(100);
    let cells: any[] = await page.evaluate(() => (window as any).__upsilonDebug.board.highlightedCells);
    expect(cells.length).toBeGreaterThan(0);

    await page.keyboard.press('Escape');
    await page.waitForTimeout(80);
    cells = await page.evaluate(() => (window as any).__upsilonDebug.board.highlightedCells);
    expect(cells).toHaveLength(0);
});

// ─── Visual snapshots ─────────────────────────────────────────────────────────

const SNAPSHOT_SCENARIOS = [
    'damage-hp-update',
    'movement-sequence',
    'skill-direct-range',
    'skill-passive-display',
    'skill-trap',
    'turn-transition',
    'game-end-win',
    'game-end-loss',
];

for (const name of SNAPSHOT_SCENARIOS) {
    test(`snapshot: ${name}`, async ({ page }) => {
        await loadScenario(page, name);

        // Ensure canvas (3D board) rendered
        const canvas = page.locator('canvas');
        await expect(canvas).toBeVisible();
        const box = await canvas.boundingBox();
        expect(box?.width).toBeGreaterThan(0);

        await expect(page).toHaveScreenshot(`sandbox-${name}.png`, { fullPage: false });
    });
}
