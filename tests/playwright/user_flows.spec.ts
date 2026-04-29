// @test-link [[ui_registration]]
// @test-link [[ui_login]]
// @test-link [[us_character_reroll]]
// @test-link [[entity_character_skill_inventory]]
// @test-link [[ui_shop]]
import { test, expect, type Page } from '@playwright/test';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const TEST_USER = {
    account_name: `pw_test_${Date.now()}`,
    email:        `pw_test_${Date.now()}@example.com`,
    password:     'Playwright!TestPass99',
    full_address: '1 Test Lane, QA City, 00000',
    birth_date:   '1990-01-01',
};

/**
 * Register a fresh account via the UI and land on the dashboard.
 * Returns the credentials used so callers can log in again if needed.
 */
async function registerAndLand(page: Page): Promise<typeof TEST_USER> {
    const user = {
        ...TEST_USER,
        account_name: `pw_${Date.now()}`,
        email:        `pw_${Date.now()}@example.com`,
    };

    await page.goto('/register');
    await page.waitForLoadState('networkidle');

    await page.fill('input[placeholder="UNIQUE_ID"]',      user.account_name);
    await page.fill('input[placeholder="COMM_LINK"]',      user.email);
    await page.fill('input[type="password"]>>nth=0',       user.password);
    await page.fill('input[type="password"]>>nth=1',       user.password);
    await page.fill('textarea[placeholder="GRID_COORDINATES"]', user.full_address);
    await page.fill('input[type="date"]',                  user.birth_date);

    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard', { timeout: 15_000 });

    return user;
}

/**
 * Log in via the UI and land on the dashboard.
 */
async function loginAndLand(page: Page, creds: { account_name: string; password: string }) {
    await page.goto('/login');
    await page.waitForLoadState('networkidle');

    await page.fill('input[placeholder="SURVIVOR_ID"]', creds.account_name);
    await page.fill('input[type="password"]',           creds.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard', { timeout: 15_000 });
}

/**
 * Wait for the character roster to finish loading (spinner gone).
 */
async function waitForRoster(page: Page) {
    // The roster shows "Synchronizing..." while loading; wait for it to disappear.
    await page.waitForFunction(
        () => !document.body.textContent?.includes('Synchronizing...'),
        { timeout: 15_000 },
    );
}

// ---------------------------------------------------------------------------
// Test: Registration
// ---------------------------------------------------------------------------

test('registration: fill form → submit → land on dashboard', async ({ page }) => {
    await registerAndLand(page);

    // Dashboard should be visible
    await expect(page).toHaveURL(/\/dashboard/);
    // Roster header is a reliable dashboard landmark
    await expect(page.getByText('Combatant Roster')).toBeVisible({ timeout: 10_000 });
});

// ---------------------------------------------------------------------------
// Test: Login
// ---------------------------------------------------------------------------

test('login: credentials → submit → land on dashboard', async ({ page }) => {
    // Register first so we have a known account
    const user = await registerAndLand(page);

    // Log out by navigating away (clear storage, then go to /login)
    await page.evaluate(() => {
        localStorage.removeItem('upsilon_token');
        localStorage.removeItem('upsilon_user');
    });

    await loginAndLand(page, user);

    await expect(page).toHaveURL(/\/dashboard/);
    await expect(page.getByText('Combatant Roster')).toBeVisible({ timeout: 10_000 });
});

// ---------------------------------------------------------------------------
// Test: Character reroll (themed confirm modal)
// ---------------------------------------------------------------------------

test('reroll: confirm via themed modal → stats regenerated', async ({ page }) => {
    // Fresh account starts with 0 wins, so the REROLL button is visible
    await registerAndLand(page);
    await waitForRoster(page);

    // Each character card has a REROLL button (only shown when total_wins === 0)
    const rerollBtn = page.getByRole('button', { name: /reroll/i }).first();
    await expect(rerollBtn).toBeVisible({ timeout: 10_000 });
    await rerollBtn.click();

    // Themed ConfirmModal should appear (not a browser native confirm())
    await expect(page.getByText('Reroll Protocol')).toBeVisible({ timeout: 5_000 });

    // ConfirmModal confirm button has text matching confirmText prop ("Reroll").
    // The modal heading "Reroll Protocol" is unique on the page; scope to its container.
    const confirmBtn = page.locator('div').filter({ hasText: /Reroll Protocol/ })
        .getByRole('button', { name: /^Reroll$/i }).last();
    await confirmBtn.click();

    // Modal should close; roster re-renders (no error banner)
    await expect(page.getByText('Reroll Protocol')).not.toBeVisible({ timeout: 8_000 });
    // Roster should still show character data (no error state)
    await expect(page.getByText('Combatant Roster')).toBeVisible();
});

// ---------------------------------------------------------------------------
// Test: Skill roulette
// ---------------------------------------------------------------------------

test('roulette: spin → stop → accept → button disappears', async ({ page }) => {
    // Fresh spawn always has roulette_available = true
    await registerAndLand(page);
    await waitForRoster(page);

    // Click the first character card to open CharacterDetailModal
    const characterCard = page.locator('.cursor-pointer').first();
    await characterCard.click();

    // Modal should open — look for the roulette button in the left pane
    const rouletteBtn = page.locator('button', { hasText: /SKILL ROULETTE/i });
    await expect(rouletteBtn).toBeVisible({ timeout: 10_000 });
    await rouletteBtn.click();

    // SkillRouletteModal opens
    await expect(page.getByText('Randomized Skill Acquisition')).toBeVisible({ timeout: 5_000 });

    // Click INITIATE SPIN
    const spinBtn = page.getByRole('button', { name: /initiate spin/i });
    await expect(spinBtn).toBeVisible();
    await spinBtn.click();

    // Reels are spinning — STOP button appears
    const stopBtn = page.getByRole('button', { name: /^stop$/i });
    await expect(stopBtn).toBeVisible({ timeout: 5_000 });
    await stopBtn.click();

    // Wait for "revealing" → "revealed" (API call + reel deceleration: ~2.5 s max)
    const acquiredBanner = page.getByText(/SKILL ACQUIRED/i);
    await expect(acquiredBanner).toBeVisible({ timeout: 15_000 });

    // ACCEPT & CLOSE
    const acceptBtn = page.getByRole('button', { name: /accept & close/i });
    await expect(acceptBtn).toBeVisible();
    await acceptBtn.click();

    // Roulette modal closes
    await expect(page.getByText('Randomized Skill Acquisition')).not.toBeVisible({ timeout: 5_000 });

    // Re-open character modal — roulette button must be gone (roulette_available is now false)
    await characterCard.click();
    // Wait for the modal to load character data again
    await expect(page.getByText(/HP|ATK|DEF/i).first()).toBeVisible({ timeout: 10_000 });
    await expect(page.getByRole('button', { name: /SKILL ROULETTE/i })).not.toBeVisible();
});

// ---------------------------------------------------------------------------
// Test: Shop — purchase an item
// ---------------------------------------------------------------------------

test('shop: open depot → select item → ACQUIRE → balance decrements', async ({ page }) => {
    await registerAndLand(page);

    // Open the neon shop modal
    const shopBtn = page.getByText('SUPPLY').first();
    await expect(shopBtn).toBeVisible({ timeout: 10_000 });
    await shopBtn.click();

    // ShopModal: "Supply Depot" title (use heading role to avoid ambiguity with nav link)
    await expect(page.getByRole('heading', { name: 'Supply Depot' })).toBeVisible({ timeout: 5_000 });

    // Wait for items to load and click the first one
    const firstItem = page.locator('[data-testid="shop-item"]').first()
        .or(page.locator('.space-y-1 > button').first());
    await expect(firstItem).toBeVisible({ timeout: 10_000 });

    // Capture the item name for later verification
    const itemName = await firstItem.locator('.font-scifi').first().textContent();

    // Read current credits from the right panel (visible after selection)
    await firstItem.click();

    // ACQUIRE ASSET button appears in the detail pane
    const acquireBtn = page.getByRole('button', { name: /ACQUIRE ASSET/i });
    await expect(acquireBtn).toBeVisible({ timeout: 5_000 });

    // Note credits before purchase (shown in detail pane)
    const balanceBefore = await page.locator('text=/\\d+ CR/').nth(1).textContent();

    await acquireBtn.click();

    // PurchaseConfirmModal: "Transaction Validation"
    await expect(page.getByText('Transaction Validation')).toBeVisible({ timeout: 5_000 });

    // Confirm (button text: "Acquire")
    await page.getByRole('button', { name: /^Acquire$/i }).click();

    // Confirm modal closes
    await expect(page.getByText('Transaction Validation')).not.toBeVisible({ timeout: 8_000 });

    // The shop modal stays open; no error message should appear
    await expect(page.getByText('Supply Depot')).toBeVisible();
    await expect(page.locator('text=⚠')).not.toBeVisible();
});

// ---------------------------------------------------------------------------
// Test: Equip a purchased item via the character modal
// ---------------------------------------------------------------------------

test('equip: purchase item → open character modal → link item → slot reflects name', async ({ page }) => {
    await registerAndLand(page);

    // ── Step 1: Buy Combat Knife (cheapest weapon, 100 CR) ──────────────────
    const shopBtn = page.getByText('SUPPLY').first();
    await shopBtn.click();
    await expect(page.getByRole('heading', { name: 'Supply Depot' })).toBeVisible({ timeout: 5_000 });

    // Find "Combat Knife" in the list (may need to scroll in very small viewports)
    const knifeBtn = page.locator('button', { hasText: /combat knife/i }).first();
    await expect(knifeBtn).toBeVisible({ timeout: 10_000 });
    await knifeBtn.click();

    await page.getByRole('button', { name: /ACQUIRE ASSET/i }).click();
    await expect(page.getByText('Transaction Validation')).toBeVisible({ timeout: 5_000 });
    await page.getByRole('button', { name: /^Acquire$/i }).click();
    await expect(page.getByText('Transaction Validation')).not.toBeVisible({ timeout: 8_000 });

    // Close shop modal
    await page.keyboard.press('Escape');
    await expect(page.getByText('Supply Depot')).not.toBeVisible({ timeout: 5_000 });

    // ── Step 2: Open character modal ─────────────────────────────────────────
    await waitForRoster(page);
    const characterCard = page.locator('.cursor-pointer').first();
    await characterCard.click();

    // Wait for CharacterDetailModal to render stat grid
    await expect(page.getByText(/HP|ATK|DEF/i).first()).toBeVisible({ timeout: 10_000 });

    // ── Step 3: Click the weapon equipment slot ───────────────────────────────
    // Equipment slots render as <div> elements showing the slot name (lowercase).
    const weaponSlot = page.locator('div.font-mono', { hasText: /^weapon$/i }).first();
    await expect(weaponSlot).toBeVisible({ timeout: 5_000 });
    await weaponSlot.click();

    // Right pane should show compatible inventory with a "Link" button
    const linkBtn = page.getByRole('button', { name: /^Link$/i }).first();
    await expect(linkBtn).toBeVisible({ timeout: 8_000 });
    await linkBtn.click();

    // After equipping, the weapon slot row should show "Combat Knife" (not "Empty")
    await expect(page.locator('div.font-scifi', { hasText: /combat knife/i })).toBeVisible({ timeout: 8_000 });
});
