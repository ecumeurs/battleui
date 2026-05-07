<!-- @spec-link [[mech_frontend_test_seams]] -->
<script setup>
import { Head } from '@inertiajs/vue3';
import { SCENARIO_LIST } from './battleSandboxScenarios.js';
</script>

<template>
    <Head title="Battle Arena Sandbox" />

    <div class="terminal">
        <header class="terminal__header">
            <div class="terminal__badge">DEV ONLY</div>
            <h1 class="terminal__title">BATTLE ARENA SANDBOX</h1>
            <p class="terminal__sub">Logic & UI fixtures — no auth, no stack, no WebSocket</p>
        </header>

        <section class="fixture-section">
            <div class="fixture-section__header">
                <span class="fixture-section__label">SCENARIOS</span>
                <span class="fixture-section__desc">Playwright baselines + manual inspection — step through fixture states</span>
            </div>
            <div class="fixture-grid">
                <a
                    v-for="s in SCENARIO_LIST"
                    :key="s.key"
                    :href="`/__test/battle/${s.key}`"
                    class="fixture-card"
                >
                    <div class="fixture-card__name">{{ s.key }}</div>
                    <div class="fixture-card__label">{{ s.label }}</div>
                    <div class="fixture-card__desc">{{ s.description }}</div>
                    <div class="fixture-card__steps">{{ s.steps }} STEP{{ s.steps !== 1 ? 'S' : '' }}</div>
                    <div class="fixture-card__arrow">ENTER →</div>
                </a>
            </div>
        </section>

        <section class="fixture-section">
            <div class="fixture-section__header fixture-section__header--visual">
                <span class="fixture-section__label">VISUAL REVIEW</span>
                <span class="fixture-section__desc">Auto-rotate camera — manual inspection only</span>
            </div>
            <div class="fixture-grid">
                <a
                    v-for="s in SCENARIO_LIST"
                    :key="'v-' + s.key"
                    :href="`/__test/battle/${s.key}?visual=1`"
                    class="fixture-card fixture-card--visual"
                >
                    <div class="fixture-card__name">{{ s.key }}</div>
                    <div class="fixture-card__label">{{ s.label }}</div>
                    <div class="fixture-card__desc">{{ s.description }}</div>
                    <div class="fixture-card__arrow">ORBIT →</div>
                </a>
            </div>
        </section>

        <footer class="terminal__footer">
            <span>{{ SCENARIO_LIST.length }} SCENARIOS REGISTERED</span>
            <span>·</span>
            <a href="/__test/component/" class="terminal__home-link">3D COMPONENT TERMINAL →</a>
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
    border-bottom: 1px solid rgba(251, 191, 36, 0.2);
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
    color: #fbbf24;
    text-shadow: 0 0 20px rgba(251, 191, 36, 0.4);
    margin: 0;
    text-transform: uppercase;
}

.terminal__sub {
    font-size: 11px;
    letter-spacing: 0.15em;
    color: rgba(251, 191, 36, 0.4);
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
    border-bottom: 1px solid rgba(251, 191, 36, 0.1);
}

.fixture-section__label {
    font-family: 'Orbitron', sans-serif;
    font-size: 11px;
    letter-spacing: 0.3em;
    color: rgba(251, 191, 36, 0.5);
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
    border: 1px solid rgba(251, 191, 36, 0.15);
    text-decoration: none;
    color: inherit;
    transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
}

.fixture-card:hover {
    border-color: rgba(251, 191, 36, 0.5);
    box-shadow: 0 0 16px rgba(251, 191, 36, 0.1);
    background: rgba(26, 26, 30, 0.9);
}

.fixture-card--visual {
    border-color: rgba(255, 0, 255, 0.12);
}
.fixture-card--visual:hover {
    border-color: rgba(255, 0, 255, 0.4);
    box-shadow: 0 0 16px rgba(255, 0, 255, 0.1);
}

.fixture-card__name {
    font-size: 9px;
    letter-spacing: 0.2em;
    color: rgba(251, 191, 36, 0.5);
    text-transform: uppercase;
}

.fixture-card__label {
    font-family: 'Orbitron', sans-serif;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.05em;
    color: #e0e0e0;
}

.fixture-card__desc {
    font-size: 10px;
    line-height: 1.5;
    color: rgba(224, 224, 224, 0.45);
    flex: 1;
}

.fixture-card__steps {
    font-size: 9px;
    letter-spacing: 0.15em;
    color: rgba(251, 191, 36, 0.4);
    text-transform: uppercase;
}

.fixture-card__arrow {
    font-size: 9px;
    letter-spacing: 0.2em;
    color: rgba(251, 191, 36, 0.35);
    text-transform: uppercase;
    margin-top: 6px;
    align-self: flex-end;
}

.terminal__footer {
    display: flex;
    align-items: center;
    gap: 20px;
    padding-top: 24px;
    border-top: 1px solid rgba(251, 191, 36, 0.1);
    font-size: 10px;
    letter-spacing: 0.15em;
    color: rgba(224, 224, 224, 0.2);
    text-transform: uppercase;
}

.terminal__home-link {
    color: rgba(251, 191, 36, 0.4);
    text-decoration: none;
    transition: color 0.15s;
}

.terminal__home-link:hover { color: #fbbf24; }
</style>
