// @test-link [[mech_frontend_test_seams]]
import { test, expect } from '@playwright/test';

const FIXTURES = [
    'grid-flat',
    'grid-elevated',
    'grid-obstacles',
    'grid-pawn-self',
    'grid-pawn-blue',
    'grid-pawn-red',
    'grid-pawn-active',
    'grid-highlight-move',
    'grid-highlight-attack',
    'grid-facing',
    'grid-full',
];

test('index page lists all fixtures', async ({ page }) => {
    await page.goto('/__test/component');
    await expect(page.locator('.terminal__title')).toContainText('COMPONENT ISOLATION TERMINAL');
    for (const name of FIXTURES) {
        await expect(page.locator(`a[href="/__test/component/${name}"]`)).toBeVisible();
    }
});

test('unknown fixture shows error panel', async ({ page }) => {
    await page.goto('/__test/component/does-not-exist');
    await page.waitForFunction(() => (window as any).__upsilonDebug?.frozen === true);
    await expect(page.locator('.viewer__error-badge')).toContainText('FIXTURE NOT FOUND');
});

for (const name of FIXTURES) {
    test(`component: ${name}`, async ({ page }) => {
        await page.goto(`/__test/component/${name}`);
        await page.waitForFunction(() => (window as any).__upsilonDebug?.frozen === true);

        const canvas = page.locator('canvas');
        await expect(canvas).toBeVisible();

        // Verify canvas has non-zero dimensions
        const box = await canvas.boundingBox();
        expect(box?.width).toBeGreaterThan(0);
        expect(box?.height).toBeGreaterThan(0);

        await expect(canvas).toHaveScreenshot(`${name}.png`);
    });
}
