// @spec-link [[ui_theme]]
// Single source of truth for all combat UI colors used in JavaScript/component logic.
// CSS counterparts live in app.css (:root), Tailwind tokens in tailwind.config.js.

export const ZONE_COLORS = {
    move:   '#00f2ff',  // cyan  — movement range
    attack: '#ff2020',  // red   — attack range
    skill:  '#fbbf24',  // gold  — skill range
};

// Team identity colors used in roster panels, timeline tokens, and pawn team indicators.
// "self" = the current user; "ally" = teammate; "enemy" = primary foe; "enemy2" = secondary foe.
export const TEAM_COLORS = {
    self:   '#00a8ff',  // blue
    ally:   '#39ff13',  // lime
    enemy:  '#ff2020',  // red
    enemy2: '#b030ff',  // purple
};

// Pawn colors on the 3D board — keyed by entity.is_self / entity.team relationship.
export const PAWN_COLORS = {
    self:  '#39ff13',  // lime  — player's own entity
    ally:  '#00a8ff',  // blue  — allied entity
    enemy: '#ff2020',  // red   — enemy entity
};

// HP bar state thresholds.
export const HP_COLORS = {
    healthy:  '#39ff13',  // > 60%
    warning:  '#ff8c00',  // > 30%
    critical: '#ff2020',  // ≤ 30%
};

// General UI accent palette — mirrors the CSS vars in app.css and Tailwind upsilon.* tokens.
export const UI_COLORS = {
    cyan:    '#00f2ff',
    magenta: '#ff00ff',
    lime:    '#39ff13',
    blue:    '#00a8ff',
    red:     '#ff2020',
    gold:    '#fbbf24',
    purple:  '#b030ff',
    orange:  '#ff8c00',
    text:    '#e0e0e0',
};

// Structural surface colors — base palette, not semantic.
// Mirrors upsilon.rust / upsilon.steel in tailwind.config.js.
export const SURFACE_COLORS = {
    tile:     '#4a4a4f',  // upsilon.steel — walkable surface
    obstacle: '#3d2b1f',  // upsilon.rust  — obstacle/hazard surface
};

/** Returns the HP bar color for a given hp / maxHp pair. */
export function hpColor(hp, maxHp) {
    const pct = maxHp ? (hp / maxHp) * 100 : 0;
    if (pct > 60) return HP_COLORS.healthy;
    if (pct > 30) return HP_COLORS.warning;
    return HP_COLORS.critical;
}
