import { test, expect, type Page } from '@playwright/test';

// @test-link [[ui_battle_arena]]
// @test-link [[rule_password_policy]]

const TEST_PASSWORD = 'Playwright!TestPass99'; // Compliant with rule_password_policy (15+ chars, Upper, Digit, Symbol)

async function registerAndLand(page: Page) {
    const userId = `pve_test_${Date.now()}`;
    const email = `${userId}@example.com`;

    await page.goto('/register');
    await page.waitForLoadState('networkidle');

    await page.fill('input[placeholder="UNIQUE_ID"]', userId);
    await page.fill('input[placeholder="COMM_LINK"]', email);
    await page.fill('input[type="password"] >> nth=0', TEST_PASSWORD);
    await page.fill('input[type="password"] >> nth=1', TEST_PASSWORD);
    await page.fill('textarea[placeholder="GRID_COORDINATES"]', '123 Test St, Cyber City');
    await page.fill('input[type="date"]', '1990-01-01');

    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard', { timeout: 15000 });
    
    return { userId, email };
}

test.describe('1v1 PVE Battle Arena', () => {
    let consoleErrors: string[] = [];
    let consoleWarnings: string[] = [];

    test.beforeEach(({ page }) => {
        consoleErrors = [];
        consoleWarnings = [];
        page.on('console', msg => {
            const text = msg.text();
            if (msg.type() === 'error') {
                consoleErrors.push(text);
            } else if (msg.type() === 'warning') {
                // Skip environment-specific WebGL performance warnings
                if (text.includes('GPU stall') || text.includes('ReadPixels')) return;
                consoleWarnings.push(text);
            }
        });
    });

    test.afterEach(async ({}, testInfo) => {
        if (testInfo.status !== 'passed') {
            console.log('--- Console Errors ---');
            consoleErrors.forEach(e => console.log(e));
            console.log('--- Console Warnings ---');
            consoleWarnings.forEach(w => console.log(w));
        }
    });

    test('should join a 1v1 PVE battle and render the arena correctly', async ({ page }) => {
        // 1. Register and reach Dashboard
        const { userId } = await registerAndLand(page);

        // 2. Join 1v1 PVE Queue
        const pveButton = page.getByRole('button', { name: /Solo \/ PVE/i });
        await expect(pveButton).toBeVisible({ timeout: 10000 });
        await pveButton.click();

        // 3. Wait for redirect to Battle Arena
        // It might take a moment to find a match even in PVE
        await page.waitForURL('**/battlearena?match_id=*', { timeout: 30000 });

        // 4. Verify Arena Layout
        // Check for the HUD (CombatHeader)
        const combatHeader = page.locator('.combat-header');
        await expect(combatHeader).toBeVisible({ timeout: 15000 });

        // Check for Team Summaries in HUD
        const allySummary = combatHeader.locator('.team-summary--left');
        await expect(allySummary, 'Allied team summary in HUD should be visible').toBeVisible();
        await expect(allySummary.locator('.team-summary__hp-text'), 'Allied team HP should be visible').toBeVisible();

        const enemySummary = combatHeader.locator('.team-summary--right');
        await expect(enemySummary, 'Hostile team summary in HUD should be visible').toBeVisible();
        await expect(enemySummary.locator('.team-summary__hp-text'), 'Hostile team HP should be visible').toBeVisible();

        // Check Clocks
        const clocks = combatHeader.locator('.game-clocks');
        await expect(clocks, 'Game clocks should be visible').toBeVisible();
        await expect(clocks.locator('.game-clocks__match-time'), 'Match duration should be visible').toContainText(/[0-9]{2}:[0-9]{2}/);
        await expect(clocks.locator('.game-clocks__shot-clock'), 'Shot clock should be visible').toBeVisible();

        // Check Side Columns (Rosters)
        // Ally Roster (Left)
        const allyRoster = page.locator('.team-roster-panel--left');
        await expect(allyRoster, 'Allied Forces roster panel should be visible').toBeVisible();
        await expect(allyRoster, 'Allied Forces header text should be present').toContainText(/ALLIED FORCES/i);
        // Should have at least one player name (the current user)
        await expect(allyRoster.locator('.roster-panel__player-name'), 'Current player name should be in allied roster').toContainText(userId, { ignoreCase: true });
        await expect(allyRoster.locator('.character-card-mini'), 'Allied roster should have 3 characters').toHaveCount(3);

        // Enemy Roster (Right)
        const enemyRoster = page.locator('.team-roster-panel--right');
        await expect(enemyRoster, 'Hostile Forces roster panel should be visible').toBeVisible();
        await expect(enemyRoster, 'Hostile Forces header text should be present').toContainText(/HOSTILE FORCES/i);
        await expect(enemyRoster.locator('.character-card-mini'), 'Hostile roster should have 3 characters').toHaveCount(3);

        // 5. Verify 3D Scene (Grid + Pawns)
        const canvas = page.locator('canvas');
        await expect(canvas, '3D Canvas should be visible').toBeVisible();

        // Verify Pawns via HTML overlays
        // There should be 6 pawns in total (3 vs 3)
        const pawnOverlays = page.locator('.pawn-overlay');
        // We'll wait a bit more for 3D initialization
        await expect(pawnOverlays, '6 Pawn overlays should be visible in the 3D scene').toHaveCount(6, { timeout: 30000 });

        expect(consoleErrors, `Console errors detected`).toHaveLength(0);
        expect(consoleWarnings, `Console warnings detected`).toHaveLength(0);
    });
});
