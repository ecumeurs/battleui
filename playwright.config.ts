// @spec-link [[mech_frontend_test_seams]]
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/playwright',
    snapshotDir: './tests/playwright/__snapshots__',
    outputDir: './tests/playwright/.output',

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

    webServer: {
        command: 'php artisan serve --host=0.0.0.0 --port=8000',
        url: 'http://localhost:8000/up',
        reuseExistingServer: !process.env.CI,
    },

    use: {
        baseURL: 'http://localhost:8000',
    },
});
