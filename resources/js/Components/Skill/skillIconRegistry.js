/**
 * Maps skill tags to inline SVG path data and neon theme colors.
 * All paths are drawn in a 32×32 viewBox, stroke-only (no fill).
 *
 * @spec-link [[ui_skill_icon]]
 * @spec-link [[req_skill_generation]]
 */
export const iconRegistry = {
    // ── Delivery ───────────────────────────────────────────────────
    melee: {
        // Crossed daggers (×)
        path: 'M5,5 L27,27 M27,5 L5,27',
        color: '#ff00ff',
    },
    ranged: {
        // Right-pointing chevron arrow (▶)
        path: 'M6,6 L26,16 L6,26 Z',
        color: '#00f2ff',
    },
    aoe: {
        // Concentric hex rings
        path: 'M16,2 L28,9 L28,23 L16,30 L4,23 L4,9 Z M16,8 L23,12 L23,20 L16,24 L9,20 L9,12 Z',
        color: '#00f2ff',
    },

    // ── Effect family ──────────────────────────────────────────────
    heal: {
        // Plus cross (+)
        path: 'M16,4 L16,28 M4,16 L28,16',
        color: '#39ff13',
    },
    shield: {
        // Hexagon outline
        path: 'M16,2 L28,9 L28,23 L16,30 L4,23 L4,9 Z',
        color: '#00f2ff',
    },
    buff: {
        // Upward triangle (△)
        path: 'M16,4 L28,26 L4,26 Z',
        color: '#39ff13',
    },
    debuff: {
        // Downward triangle (▽)
        path: 'M16,28 L28,6 L4,6 Z',
        color: '#ff00ff',
    },
    dot: {
        // Dripping rhombus
        path: 'M16,4 L28,14 L16,24 L4,14 Z M16,24 L14,30 M16,24 L18,30',
        color: '#39ff13',
    },
    stun: {
        // Zigzag bolt (⚡)
        path: 'M20,4 L12,16 L18,16 L10,28',
        color: '#fbbf24',
    },

    // ── Modifiers ──────────────────────────────────────────────────
    crit: {
        // 6-line asterisk (*)
        path: 'M16,4 L16,28 M4,16 L28,16 M7,7 L25,25 M25,7 L7,25',
        color: '#fbbf24',
    },
    mobility: {
        // Double chevron (»)
        path: 'M6,8 L14,16 L6,24 M16,8 L24,16 L16,24',
        color: '#00f2ff',
    },
    channeled: {
        // Hourglass
        path: 'M4,4 L28,4 L16,16 L28,28 L4,28 L16,16 Z',
        color: '#4a4a4f',
    },
    instant: {
        // Lightning chevron
        path: 'M20,4 L10,16 L17,16 L8,28',
        color: '#00f2ff',
    },

    // ── Behavior ───────────────────────────────────────────────────
    trap: {
        // Trapezoid mine outline
        path: 'M6,22 L26,22 L29,10 L3,10 Z M16,10 L16,6',
        color: '#fbbf24',
    },
    counter: {
        // Mirrored arrows (⇄)
        path: 'M4,10 L12,4 L12,8 L20,8 L20,4 L28,10 L20,16 L20,12 L12,12 L12,16 Z M4,22 L12,28 L12,24 L20,24 L20,28 L28,22',
        color: '#ff00ff',
    },
    reaction: {
        // Bounced arrow
        path: 'M4,20 L4,12 L20,12 L20,6 L28,16 L20,26 L20,20 L10,20 L10,26 Z',
        color: '#ff00ff',
    },
    passive: {
        // Infinity loop (∞)
        path: 'M16,16 C12,8 4,8 4,16 C4,24 12,24 16,16 C20,8 28,8 28,16 C28,24 20,24 16,16 Z',
        color: '#4a4a4f',
    },

    // ── Fallback ───────────────────────────────────────────────────
    _fallback: {
        // Question mark / neutral cross
        path: 'M16,4 L16,28 M4,16 L28,16',
        color: '#4a4a4f',
    },
};

/**
 * Returns the registry entry for a tag, falling back to _fallback for unknown tags.
 */
export function getIcon(tag) {
    return iconRegistry[tag] ?? iconRegistry._fallback;
}
