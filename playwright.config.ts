// @spec-link [[mech_frontend_test_seams]]
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/playwright',
    snapshotDir: './tests/playwright/__snapshots__',
    outputDir: './tests/playwright/.output',

    // Bound runaway specs so a hang can't stall the whole CI job.
    timeout: 60_000,
    retries: process.env.CI ? 1 : 0,
    forbidOnly: !!process.env.CI,

    // HTML report is what CI looks for (battleui/playwright-report/) and uploads
    // as an artifact; without it CI prints "No HTML Report found".
    reporter: [
        ['list'],
        ['html', { outputFolder: 'playwright-report', open: 'never' }],
    ],

    expect: {
        toHaveScreenshot: {
            // 0.1% pixel diff tolerance — tight enough to catch regressions,
            // loose enough that SwiftShader sub-pixel variance doesn't flake.
            maxDiffPixelRatio: 0.001,
        },
    },

    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],

    webServer: process.env.PLAYWRIGHT_SKIP_SERVER ? undefined : [
        {
            command: 'php artisan serve --host=0.0.0.0 --port=8000',
            url: 'http://localhost:8000/up',
            reuseExistingServer: !process.env.CI,
        },
        {
            command: 'php artisan reverb:start --host=0.0.0.0 --port=8080',
            url: 'http://127.0.0.1:8080',
            reuseExistingServer: !process.env.CI,
        },
    ],

    use: {
        baseURL: 'http://localhost:8000',
    },
});
