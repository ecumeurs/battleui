/**
 * @spec-link [[rule_stat_taxonomy_classA_classB]]
 * @spec-link [[rule_progression]]
 */
export function useCharacterStats() {
    const STAT_METADATA = {
        hp: { label: 'HP', classA: true, cpCost: 1 },
        mp: { label: 'MP', classA: true, cpCost: 1 },
        sp: { label: 'SP', classA: true, cpCost: 1 },
        attack: { label: 'ATK', classA: true, cpCost: 5 },
        defense: { label: 'DEF', classA: true, cpCost: 5 },
        movement: { label: 'MOV', classA: true, cpCost: 30 },
        jump_height: { label: 'JMP', classA: true, cpCost: 15 },
        crit_chance: { label: 'CRT%', classA: true, cpCost: 10 },
        crit_damage: { label: 'CRT+', classA: true, cpCost: 5 },
        attack_range: { label: 'RNG', classA: false },
        shield: { label: 'SHD', classA: false }
    };

    const calculateStats = (character) => {
        const rows = [];
        const equipment = character.equipment || {};
        
        // Sum contributions from all equipped items
        const contributions = {
            hp: 0, mp: 0, sp: 0, attack: 0, defense: 0, 
            movement: 0, jump_height: 0, crit_chance: 0, crit_damage: 0,
            attack_range: 0, shield: 0,
            ArmorRating: 0, WeaponBaseDamage: 0 // Aliases for engine parity
        };

        const equipmentItems = [
            equipment.armor_item,
            equipment.utility_item,
            equipment.weapon_item
        ].filter(Boolean);

        equipmentItems.forEach(item => {
            const props = item.shop_item?.properties || {};
            Object.entries(props).forEach(([key, val]) => {
                // Map camelCase from engine properties to snake_case stats
                const keyMap = {
                    'ArmorRating': 'defense',
                    'WeaponBaseDamage': 'attack',
                    'WeaponRange': 'attack_range',
                    'Movement': 'movement',
                    'JumpHeight': 'jump_height',
                    'CritChance': 'crit_chance',
                    'CritDamage': 'crit_damage'
                };
                
                const statKey = keyMap[key] || key.toLowerCase();
                if (contributions[statKey] !== undefined) {
                    contributions[statKey] += val;
                }
            });
        });

        // Build result rows
        Object.entries(STAT_METADATA).forEach(([key, meta]) => {
            const base = character[key] || 0;
            const bonus = contributions[key] || 0;
            rows.push({
                key,
                label: meta.label,
                base,
                bonus,
                effective: base + bonus,
                cpCost: meta.cpCost,
                classA: meta.classA
            });
        });

        return rows;
    };

    const calculateCp = (character, totalWins) => {
        const maxCp = 100 + (totalWins * 10);
        const spentCp = character.spent_cp || 0;
        return {
            spent: spentCp,
            max: maxCp,
            percent: Math.min(100, (spentCp / maxCp) * 100)
        };
    };

    return {
        calculateStats,
        calculateCp,
        STAT_METADATA
    };
}
