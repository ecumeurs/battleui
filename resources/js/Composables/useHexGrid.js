import { computed } from 'vue';

export const SUBDIVISION = 4;
export const HEX_RADIUS = 1.0 / (1.5 * SUBDIVISION);
export const DX = 1.5 * HEX_RADIUS;
export const DZ = Math.sqrt(3) * HEX_RADIUS;

/**
 * Returns the world position [X, Y, Z] for a hex at logical (hx, hy).
 */
export function getHexWorldPosition(hx, hy, height = 0) {
    const x = hx * DX;
    const z = (hy + (hx % 2) * 0.5) * DZ;
    return [x, height, z];
}

/**
 * Returns all hexes that belong to a logical tile (gx, gy).
 * A hex belongs to a tile if its center is within the tile's 1x1 area.
 * The arena uses center-based coordinates (centers at integer gx, gy).
 */
export function getHexesInTile(gx, gy) {
    const hexes = [];
    
    // Bounds in hex-space (over-estimated)
    const hxStart = Math.floor((gx - 0.5) / DX) - 1;
    const hxEnd = Math.ceil((gx + 0.5) / DX) + 1;
    const hyStart = Math.floor((gy - 0.5) / DZ) - 1;
    const hyEnd = Math.ceil((gy + 0.5) / DZ) + 1;

    for (let hx = hxStart; hx <= hxEnd; hx++) {
        for (let hy = hyStart; hy <= hyEnd; hy++) {
            const [x, _, z] = getHexWorldPosition(hx, hy);
            
            // Check if center is within the square centered at (gx, gy)
            if (x >= gx - 0.5 && x < gx + 0.5 && z >= gy - 0.5 && z < gy + 0.5) {
                hexes.push({ hx, hy, x, z });
            }
        }
    }
    
    return hexes;
}

/**
 * Returns the world position [X, Z] for the center of a logical tile (gx, gy).
 * In our square-logic-on-hex-visuals, this is just the grid center.
 */
export function getTileCenter(gx, gy) {
    return [gx, gy];
}
