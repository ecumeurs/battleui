<!-- @spec-link [[mech_frontend_test_seams]] -->
<script setup>
import { Head } from '@inertiajs/vue3';

const FIXTURES = [
    { name: 'grid-flat',             label: 'Grid: Flat 5×5',           description: 'Plain flat terrain, height=0, no entities or obstacles' },
    { name: 'grid-elevated',         label: 'Grid: Elevated Terrain',   description: 'Varied cell heights — the new 3D elevation dimension' },
    { name: 'grid-obstacles',        label: 'Grid: Obstacles',          description: 'Obstacle blocks extruded above tile surface' },
    { name: 'grid-pawn-self',        label: 'Pawn: Self (Green)',       description: 'Own character pawn, is_self=true, neon lime' },
    { name: 'grid-pawn-blue',        label: 'Pawn: Ally (Blue)',        description: 'Allied pawn, team 1, electric blue' },
    { name: 'grid-pawn-red',         label: 'Pawn: Enemy (Red)',        description: 'Enemy pawn, team 2, hazard red' },
    { name: 'grid-pawn-active',      label: 'Pawn: Active Turn',       description: 'Active-turn pawn with enhanced emissive glow' },
    { name: 'grid-highlight-move',   label: 'Highlight: Move Range',   description: 'Cyan semi-transparent movement range slabs' },
    { name: 'grid-highlight-attack', label: 'Highlight: Attack Range', description: 'Magenta semi-transparent attack target slabs' },
    { name: 'grid-facing',           label: 'Pawn: Facing Indicator',  description: 'Dark-green triangle at cell level — four pawns facing each cardinal direction' },
    { name: 'grid-full',             label: 'Grid: Full Scene',         description: 'Elevated terrain + obstacles + mixed pawns + highlights' },
];
</script>

<template>
    <Head title="Component Isolation Terminal" />

    <div class="terminal">
        <header class="terminal__header">
            <div class="terminal__badge">DEV ONLY</div>
            <h1 class="terminal__title">COMPONENT ISOLATION TERMINAL</h1>
            <p class="terminal__sub">3D scene fixtures — no auth required — non-production only</p>
        </header>

        <!-- Automated test fixtures -->
        <section class="fixture-section">
            <div class="fixture-section__header">
                <span class="fixture-section__label">AUTOMATED</span>
                <span class="fixture-section__desc">Playwright snapshot baselines — static, frozen</span>
            </div>
            <div class="fixture-grid">
                <a
                    v-for="f in FIXTURES"
                    :key="f.name"
                    :href="`/__test/component/${f.name}`"
                    class="fixture-card"
                >
                    <div class="fixture-card__name">{{ f.name }}</div>
                    <div class="fixture-card__label">{{ f.label }}</div>
                    <div class="fixture-card__desc">{{ f.description }}</div>
                    <div class="fixture-card__arrow">ENTER →</div>
                </a>
            </div>
        </section>

        <!-- Visual review fixtures -->
        <section class="fixture-section">
            <div class="fixture-section__header fixture-section__header--visual">
                <span class="fixture-section__label">VISUAL REVIEW</span>
                <span class="fixture-section__desc">Auto-rotating — for manual inspection only, not snapshotted</span>
            </div>
            <div class="fixture-grid">
                <a
                    v-for="f in FIXTURES"
                    :key="'v-' + f.name"
                    :href="`/__test/component/${f.name}?visual=1`"
                    class="fixture-card fixture-card--visual"
                >
                    <div class="fixture-card__name">{{ f.name }}</div>
                    <div class="fixture-card__label">{{ f.label }}</div>
                    <div class="fixture-card__desc">{{ f.description }}</div>
                    <div class="fixture-card__arrow">ORBIT →</div>
                </a>
            </div>
        </section>

        <!-- Link to Battle Arena Sandbox -->
        <section class="fixture-section">
            <div class="fixture-section__header">
                <span class="fixture-section__label" style="color: rgba(251,191,36,0.5)">BATTLE ARENA SANDBOX</span>
                <span class="fixture-section__desc">Logic & UI fixture harness — HP bars, skills, movement, animations</span>
            </div>
            <div>
                <a href="/__test/battle/" class="battle-sandbox-link">
                    <span>OPEN BATTLE ARENA SANDBOX →</span>
                    <span style="opacity:0.5; font-size:10px;">/__test/battle/</span>
                </a>
            </div>
        </section>

        <footer class="terminal__footer">
            <span>{{ FIXTURES.length }} FIXTURES REGISTERED</span>
            <span>·</span>
            <span>PLAYWRIGHT LAYER 2 SEAM ACTIVE</span>
            <span>·</span>
            <a href="/" class="terminal__home-link">← RETURN TO BASE</a>
        </footer>
    </div>
</template>

<style scoped>
.terminal {
    min-height: 100vh;
    background: #05050a;
    color: #e0e0e0;
    font-family: 'JetBrains Mono', 'Courier New', monospace;
    padding: 48px 40px;
    display: flex;
    flex-direction: column;
    gap: 40px;
}

.terminal__header {
    display: flex;
    flex-direction: column;
    gap: 8px;
    border-bottom: 1px solid rgba(0, 242, 255, 0.2);
    padding-bottom: 24px;
}

.terminal__badge {
    font-size: 10px;
    letter-spacing: 0.4em;
    color: #ff2020;
    border: 1px solid rgba(255, 32, 32, 0.4);
    padding: 2px 8px;
    display: inline-block;
    width: fit-content;
}

.terminal__title {
    font-family: 'Orbitron', sans-serif;
    font-size: 28px;
    font-weight: 700;
    letter-spacing: 0.2em;
    color: #00f2ff;
    text-shadow: 0 0 20px rgba(0, 242, 255, 0.4);
    margin: 0;
    text-transform: uppercase;
}

.terminal__sub {
    font-size: 11px;
    letter-spacing: 0.15em;
    color: rgba(0, 242, 255, 0.4);
    margin: 0;
    text-transform: uppercase;
}

.fixture-section {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.fixture-section__header {
    display: flex;
    align-items: baseline;
    gap: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(0, 242, 255, 0.1);
}

.fixture-section__label {
    font-family: 'Orbitron', sans-serif;
    font-size: 11px;
    letter-spacing: 0.3em;
    color: rgba(0, 242, 255, 0.5);
    text-transform: uppercase;
}

.fixture-section__header--visual .fixture-section__label {
    color: rgba(255, 0, 255, 0.6);
}

.fixture-section__desc {
    font-size: 10px;
    letter-spacing: 0.1em;
    color: rgba(224, 224, 224, 0.2);
    text-transform: uppercase;
}

.fixture-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.fixture-card {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 20px;
    background: rgba(26, 26, 30, 0.6);
    border: 1px solid rgba(0, 242, 255, 0.15);
    text-decoration: none;
    color: inherit;
    transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    cursor: pointer;
}

.fixture-card:hover {
    border-color: rgba(0, 242, 255, 0.5);
    background: rgba(26, 26, 30, 0.9);
    box-shadow: 0 0 16px rgba(0, 242, 255, 0.08), inset 0 0 12px rgba(0, 242, 255, 0.03);
}

.fixture-card__name {
    font-size: 10px;
    letter-spacing: 0.3em;
    color: rgba(0, 242, 255, 0.5);
    text-transform: uppercase;
}

.fixture-card__label {
    font-family: 'Orbitron', sans-serif;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.05em;
    color: #e0e0e0;
    text-transform: uppercase;
}

.fixture-card__desc {
    font-size: 11px;
    color: rgba(224, 224, 224, 0.45);
    line-height: 1.5;
    flex: 1;
}

.fixture-card__arrow {
    font-size: 10px;
    letter-spacing: 0.25em;
    color: rgba(0, 242, 255, 0.3);
    margin-top: 4px;
    transition: color 0.15s;
}

.fixture-card:hover .fixture-card__arrow {
    color: #00f2ff;
}

.fixture-card--visual {
    border-color: rgba(255, 0, 255, 0.1);
}

.fixture-card--visual:hover {
    border-color: rgba(255, 0, 255, 0.4);
    box-shadow: 0 0 16px rgba(255, 0, 255, 0.06), inset 0 0 12px rgba(255, 0, 255, 0.02);
}

.fixture-card--visual .fixture-card__arrow {
    color: rgba(255, 0, 255, 0.3);
}

.fixture-card--visual:hover .fixture-card__arrow {
    color: #ff00ff;
}

.terminal__footer {
    display: flex;
    align-items: center;
    gap: 16px;
    font-size: 10px;
    letter-spacing: 0.2em;
    color: rgba(224, 224, 224, 0.25);
    text-transform: uppercase;
    border-top: 1px solid rgba(0, 242, 255, 0.1);
    padding-top: 20px;
}

.terminal__home-link {
    color: rgba(0, 242, 255, 0.35);
    text-decoration: none;
    transition: color 0.15s;
}

.terminal__home-link:hover {
    color: #00f2ff;
}

.battle-sandbox-link {
    display: inline-flex;
    align-items: center;
    gap: 16px;
    padding: 16px 24px;
    background: rgba(251, 191, 36, 0.06);
    border: 1px solid rgba(251, 191, 36, 0.25);
    color: #fbbf24;
    text-decoration: none;
    font-family: 'Orbitron', sans-serif;
    font-size: 12px;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
}

.battle-sandbox-link:hover {
    border-color: rgba(251, 191, 36, 0.5);
    box-shadow: 0 0 16px rgba(251, 191, 36, 0.1);
    background: rgba(251, 191, 36, 0.1);
}
</style>
