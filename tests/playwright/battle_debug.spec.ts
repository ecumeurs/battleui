import { test, expect, type Page } from '@playwright/test';
import * as fs from 'fs';

// @test-link [[ui_battle_arena]]

const TEST_PASSWORD = 'Playwright!TestPass99';

async function registerAndLand(page: Page) {
    const userId = `pve_debug_${Date.now()}`;
    const email = `${userId}@example.com`;

    await page.goto('/register');
    await page.waitForLoadState('networkidle');

    await page.fill('input[placeholder="UNIQUE_ID"]', userId);
    await page.fill('input[placeholder="COMM_LINK"]', email);
    await page.fill('input[type="password"] >> nth=0', TEST_PASSWORD);
    await page.fill('input[type="password"] >> nth=1', TEST_PASSWORD);
    await page.fill('textarea[placeholder="GRID_COORDINATES"]', '123 Debug St, Cyber City');
    await page.fill('input[type="date"]', '1990-01-01');

    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard', { timeout: 15000 });
    
    return { userId, email };
}

test('battle debug: gather logs and DOM for 1v1 PVE', async ({ page }) => {
    const logs: string[] = [];
    const errors: string[] = [];

    // Capture console logs
    page.on('console', msg => {
        const timestamp = new Date().toISOString();
        logs.push(`[${timestamp}] [${msg.type()}] ${msg.text()}`);
    });

    // Capture unhandled exceptions
    page.on('pageerror', err => {
        const timestamp = new Date().toISOString();
        errors.push(`[${timestamp}] [ERROR] ${err.stack || err.message}`);
    });

    // 1. Register and reach Dashboard
    await registerAndLand(page);

    // 2. Join 1v1 PVE Queue
    const pveButton = page.getByRole('button', { name: /Solo \/ PVE/i });
    await expect(pveButton).toBeVisible({ timeout: 10000 });
    await pveButton.click();

    // 3. Wait for redirect to Battle Arena
    await page.waitForURL('**/battlearena?match_id=*', { timeout: 30000 });

    // 4. Wait for the arena to stabilize
    // We wait for the canvas or the combat header
    await page.waitForSelector('.combat-header, canvas', { timeout: 15000 });

    // Give 2 seconds for scene initialization as requested
    await page.waitForTimeout(2000);

    // 5. Capture the DOM
    const content = await page.content();

    // 6. Prepare log content
    const allLogs = [
        '=== CONSOLE LOGS ===',
        ...logs,
        '',
        '=== PAGE ERRORS ===',
        ...errors,
    ].join('\n');

    // Write files to the root of the battleui folder
    fs.writeFileSync('battle_js_log.log', allLogs);
    fs.writeFileSync('battle_dom.html', content);

    console.log('Successfully captured battle_js_log.log and battle_dom.html');
    
    // Perform a basic assertion to report success/failure in the test runner
    // If the roster is missing (as observed), this will fail but files are already written.
    await expect(page.locator('.team-roster-panel--left'), 'Allied roster should be present').toBeVisible({ timeout: 5000 });
});
