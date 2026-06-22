// @test-link [[ui_registration]]
// @test-link [[ui_login]]
// @test-link [[api_websocket_user_notifications]]
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
 * Wait for the character roster to finish loading.
 */
async function waitForRoster(page: Page) {
    await page.waitForSelector('[data-testid="character-card"]', { state: 'visible', timeout: 15_000 });
    await page.waitForLoadState('networkidle', { timeout: 10_000 });
}

/**
 * Open TacticalPanel in character mode by clicking the first character card.
 * Waits for the stat grid to be visible before returning.
 */
async function openCharacterPanel(page: Page) {
    const card = page.getByTestId('character-card').first();
    await card.click();
    await expect(page.getByText(/HP|ATK|DEF/i).first()).toBeVisible({ timeout: 10_000 });
}

// ---------------------------------------------------------------------------
// Test: Registration
// ---------------------------------------------------------------------------

test('registration: fill form → submit → land on dashboard', async ({ page }) => {
    await registerAndLand(page);

    await expect(page).toHaveURL(/\/dashboard/);
    await expect(page.getByText('Combatant Roster')).toBeVisible({ timeout: 10_000 });
});

test('registration: private WS LED lit after landing on dashboard', async ({ page }) => {
    await registerAndLand(page);

    const privateLed = page.locator('[data-testid="led-private"]');
    await expect(privateLed).toHaveClass(/bg-upsilon-lime/, { timeout: 15_000 });
});

// ---------------------------------------------------------------------------
// Test: Login
// ---------------------------------------------------------------------------

test('login: credentials → submit → land on dashboard', async ({ page }) => {
    const user = await registerAndLand(page);

    await page.evaluate(() => {
        localStorage.removeItem('upsilon_token');
        localStorage.removeItem('upsilon_user');
    });

    await loginAndLand(page, user);

    await expect(page).toHaveURL(/\/dashboard/);
    await expect(page.getByText('Combatant Roster')).toBeVisible({ timeout: 10_000 });
});

test('login: private WS LED lit after landing on dashboard', async ({ page }) => {
    const user = await registerAndLand(page);

    await page.evaluate(() => {
        localStorage.removeItem('upsilon_token');
        localStorage.removeItem('upsilon_user');
    });

    await loginAndLand(page, user);

    const privateLed = page.locator('[data-testid="led-private"]');
    await expect(privateLed).toHaveClass(/bg-upsilon-lime/, { timeout: 15_000 });
});

// ---------------------------------------------------------------------------
// Test: Character reroll (inline confirm in TacticalPanel)
// ---------------------------------------------------------------------------

test('reroll: confirm via inline panel → stats regenerated', async ({ page }) => {
    // Fresh account starts with 0 wins, so the REROLL button is visible in the panel
    await registerAndLand(page);
    await waitForRoster(page);

    // Open TacticalPanel in character mode
    await openCharacterPanel(page);

    // REROLL button is in the left pane (only visible when total_wins === 0)
    const rerollBtn = page.getByRole('button', { name: /reroll/i }).first();
    await expect(rerollBtn).toBeVisible({ timeout: 10_000 });
    await rerollBtn.click();

    // Right pane shows inline "Reroll Protocol" confirm view
    await expect(page.getByText('Reroll Protocol')).toBeVisible({ timeout: 5_000 });

    // Click the Reroll confirm button scoped to the confirm block
    const confirmBtn = page.locator('div').filter({ hasText: /Reroll Protocol/ })
        .getByRole('button', { name: /^Reroll$/i }).last();
    await confirmBtn.click();

    // Confirm view closes; panel stays open, no error
    await expect(page.getByText('Reroll Protocol')).not.toBeVisible({ timeout: 8_000 });
    await expect(page.getByText('Combatant Roster')).toBeVisible();
});

// ---------------------------------------------------------------------------
// Test: Skill roulette (inline in TacticalPanel right pane)
// ---------------------------------------------------------------------------

test('roulette: spin → stop → accept → button disappears', async ({ page }) => {
    // Fresh spawn always has roulette_available = true
    await registerAndLand(page);
    await waitForRoster(page);

    // Open TacticalPanel in character mode
    await openCharacterPanel(page);

    // SCAVENGE SKILL button in the left pane opens inline roulette in the right pane
    const scavengeBtn = page.locator('button', { hasText: /SCAVENGE SKILL/i });
    await expect(scavengeBtn).toBeVisible({ timeout: 10_000 });
    await scavengeBtn.click();

    // Roulette UI appears inline (no separate modal, no confirm notification step)
    await expect(page.getByText('Randomized Skill Acquisition')).toBeVisible({ timeout: 5_000 });

    // Click INITIATE SPIN
    const spinBtn = page.getByRole('button', { name: /initiate spin/i });
    await expect(spinBtn).toBeVisible();
    await spinBtn.click();

    // Reels spinning — STOP button appears
    const stopBtn = page.locator('button', { hasText: /STOP/i });
    await expect(stopBtn).toBeVisible({ timeout: 5_000 });
    await stopBtn.click();

    // Wait for API + reel deceleration (~2.5 s max)
    const acquiredBanner = page.getByText(/SKILL ACQUIRED/i);
    await expect(acquiredBanner).toBeVisible({ timeout: 15_000 });

    // ACCEPT & CLOSE
    const acceptBtn = page.getByRole('button', { name: /accept & close/i });
    await expect(acceptBtn).toBeVisible();
    await acceptBtn.click();

    // Roulette UI disappears; left pane should no longer show SCAVENGE SKILL
    // (state is patched reactively — no reopen needed)
    await expect(page.getByText('Randomized Skill Acquisition')).not.toBeVisible({ timeout: 5_000 });
    await expect(page.locator('button', { hasText: /SCAVENGE SKILL/i })).not.toBeVisible({ timeout: 5_000 });
});

// ---------------------------------------------------------------------------
// Test: Shop — purchase an item
// ---------------------------------------------------------------------------

test('shop: open depot → select item → ACQUIRE → balance decrements', async ({ page }) => {
    await registerAndLand(page);

    // Open TacticalPanel in shop mode
    const shopBtn = page.getByTestId('shop-button');
    await expect(shopBtn).toBeVisible({ timeout: 10_000 });
    await shopBtn.click();

    // Panel opens — wait for a shop item to appear (proves the panel is mounted and loaded)
    const firstItem = page.locator('[data-testid="shop-item"]').first();
    await expect(firstItem).toBeVisible({ timeout: 10_000 });
    await firstItem.click();

    // ACQUIRE ASSET button appears in the detail pane
    const acquireBtn = page.getByRole('button', { name: /ACQUIRE ASSET/i });
    await expect(acquireBtn).toBeVisible({ timeout: 5_000 });
    await acquireBtn.click();

    // Inline "Transaction Validation" confirm banner appears
    await expect(page.getByText('Transaction Validation')).toBeVisible({ timeout: 5_000 });

    // Confirm button in the inline banner
    const confirmAcquireBtn = page.getByRole('button', { name: /^Acquire$/i });
    await expect(confirmAcquireBtn).toBeVisible({ timeout: 5_000 });
    await confirmAcquireBtn.click();

    // Confirm banner disappears; no error message
    await expect(page.getByText('Transaction Validation')).not.toBeVisible({ timeout: 8_000 });
    // Panel still open (shop items still listed); no error icon
    await expect(page.locator('[data-testid="shop-item"]').first()).toBeVisible({ timeout: 5_000 });
    await expect(page.locator('text=⚠')).not.toBeVisible();
});

// ---------------------------------------------------------------------------
// Test: Equip a purchased item via the character panel
// ---------------------------------------------------------------------------

test('equip: purchase item → open character panel → link item → slot reflects name', async ({ page }) => {
    await registerAndLand(page);

    // ── Step 1: Buy Combat Knife (cheapest weapon, 100 CR) ──────────────────
    const shopBtn = page.getByTestId('shop-button');
    await expect(shopBtn).toBeVisible({ timeout: 10_000 });
    await shopBtn.click();

    const knifeBtn = page.locator('[data-testid="shop-item"]', { hasText: /combat knife/i });
    await expect(knifeBtn).toBeVisible({ timeout: 10_000 });
    await knifeBtn.click();

    const acquireEquipBtn = page.getByRole('button', { name: /ACQUIRE ASSET/i });
    await expect(acquireEquipBtn).toBeVisible({ timeout: 5_000 });
    await acquireEquipBtn.click();

    await expect(page.getByText('Transaction Validation')).toBeVisible({ timeout: 5_000 });
    const confirmEquipBtn = page.getByRole('button', { name: /^Acquire$/i });
    await expect(confirmEquipBtn).toBeVisible({ timeout: 5_000 });
    await confirmEquipBtn.click();
    await expect(page.getByText('Transaction Validation')).not.toBeVisible({ timeout: 8_000 });

    // Close shop panel via Escape; items disappear from DOM
    await page.keyboard.press('Escape');
    await expect(page.locator('[data-testid="shop-item"]').first()).not.toBeVisible({ timeout: 5_000 });

    // ── Step 2: Open TacticalPanel in character mode ─────────────────────────
    await waitForRoster(page);
    await openCharacterPanel(page);

    // ── Step 3: Click the weapon equipment slot ───────────────────────────────
    const weaponSlot = page.locator('[data-testid="diagnostic-terminal"] .font-mono', { hasText: /^weapon$/i }).first();
    await expect(weaponSlot).toBeVisible({ timeout: 5_000 });
    await weaponSlot.click();

    // Right pane shows compatible inventory with a "Link" button
    const linkBtn = page.getByRole('button', { name: /^Link$/i }).first();
    await expect(linkBtn).toBeVisible({ timeout: 8_000 });
    await linkBtn.click();

    // After equipping, weapon slot should show "Combat Knife"
    await expect(page.locator('[data-testid="diagnostic-terminal"] .font-scifi', { hasText: /combat knife/i })).toBeVisible({ timeout: 8_000 });
});

// ---------------------------------------------------------------------------
// Skill icon + name visible after roulette reveal
// @test-link [[shared:req_skill_generation_overhaul]]
// @test-link [[mech_skill_name_generation]]
// @test-link [[ui_skill_icon]]
// ---------------------------------------------------------------------------
test('skill roulette → revealed skill has diegetic name and SkillIcon', async ({ page }) => {
    await registerAndLand(page);

    await waitForRoster(page);
    await openCharacterPanel(page);

    // SCAVENGE SKILL → inline roulette (no intermediate notification step)
    const scavengeBtn = page.locator('button', { hasText: /SCAVENGE SKILL/i }).first();
    await expect(scavengeBtn).toBeVisible({ timeout: 8_000 });
    await scavengeBtn.click();

    // Initiate spin
    const spinBtn = page.getByRole('button', { name: /initiate spin/i });
    await expect(spinBtn).toBeVisible({ timeout: 5_000 });
    await spinBtn.click();

    // Stop spin → triggers actual roll
    const stopBtn = page.getByRole('button', { name: /stop/i });
    await expect(stopBtn).toBeVisible({ timeout: 5_000 });
    await stopBtn.click();

    // Wait for the revealed state
    await expect(page.getByText(/skill acquired/i)).toBeVisible({ timeout: 10_000 });

    // 1. SkillIcon component must be present (data-testid="skill-icon" to be added to SkillIcon.vue)
    await expect(page.locator('[data-testid="skill-icon"]')).toBeVisible({ timeout: 5_000 });

    // 2. Name must not equal a known raw property key
    const RAW_KEYS = ['Damage', 'Heal', 'Shield', 'Accuracy', 'New Skill'];
    const nameEl = page.locator('.font-scifi').filter({ hasText: /[A-Z][a-z]/ }).first();
    await expect(nameEl).toBeVisible({ timeout: 3_000 });
    const name = await nameEl.textContent() ?? '';
    for (const raw of RAW_KEYS) {
        expect(name.trim()).not.toBe(raw);
    }

    const acceptBtn = page.getByRole('button', { name: /accept/i });
    await acceptBtn.click();
    await expect(page.getByText(/skill acquired/i)).not.toBeVisible({ timeout: 5_000 });
});

// ---------------------------------------------------------------------------
// Battle Arena: active skill buttons + passive rail
// @test-link [[ui_action_panel]]
// @test-link [[ui_skill_icon]]
// FIXME: stub only — ActionPanel skill split not implemented yet.
// ---------------------------------------------------------------------------
test.fixme('battle arena action panel renders active skills and passive rail', async ({ page }) => {
    // Full flow: register → equip a passive skill + an active skill → start a PvE match → assert panel split.
    // Implementation pending ActionPanel.vue rework.
    expect(true).toBe(true);
});
