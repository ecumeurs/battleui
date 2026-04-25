// @test-link [[mech_frontend_test_seams]]
import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

test('visual smoke test: gather logs and DOM', async ({ page }) => {
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

    // Navigate to the full scene with visual=1
    // We use the base URL from playwright.config.ts (localhost:8000)
    await page.goto('/__test/component/grid-full?visual=1');

    // Wait for the debug seam to be ready
    await page.waitForFunction(() => (window as any).__upsilonDebug?.ready === true);

    // Give some time for FX and auto-rotate to initialize
    await page.waitForTimeout(2000);

    // Capture the DOM
    const content = await page.content();

    // Prepare log content
    const allLogs = [
        '=== CONSOLE LOGS ===',
        ...logs,
        '',
        '=== PAGE ERRORS ===',
        ...errors,
    ].join('\n');

    // Write files to the root of the battleui folder (where playwright is likely run from)
    // In a dev container / CI environment, we want these in the project root.
    fs.writeFileSync('js_log.log', allLogs);
    fs.writeFileSync('dom.html', content);

    console.log('Successfully captured js_log.log and dom.html');
});
