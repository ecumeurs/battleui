/**
 * useActionDispatch — action selection, pathfinding, range calculation, and dispatch.
 *
 * Handles:
 *  - selectedAction / selectedPath / highlightedCells state
 *  - isProcessing flag
 *  - handleAction (move / attack / pass / forfeit / skill)
 *  - handleTileClick (executes the selected action)
 *  - Keyboard shortcut handler (handleKeydown)
 *  - Range calculations (move BFS, attack ring, skill diamond + LOS)
 *  - executeForfeit
 */
import { ref } from 'vue';
import { game } from '@/services/game';

export function useActionDispatch(
    matchId,
    gameState,
    currentEntityId,
    currentEntity,
    isPlayerTurn,
    allEntities,
    enemyEntities,
    allyEntities,
    grid,
) {
    const selectedAction    = ref(null);
    const selectedPath      = ref([]);
    const highlightedCells  = ref([]);
    const isProcessing      = ref(false);
    const showForfeitModal  = ref(false);

    // ─── Pathfinding helpers ──────────────────────────────────────────────────

    function getNeighbors(x, y) {
        const neighbors = [];
        const dirs = [[0, -1], [1, 0], [0, 1], [-1, 0]];
        for (const d of dirs) {
            const nx = x + d[0];
            const ny = y + d[1];
            if (nx >= 0 && nx < grid.value.width && ny >= 0 && ny < grid.value.height) {
                const cell = grid.value.cells[nx] && grid.value.cells[nx][ny];
                if (cell && !cell.obstacle) {
                    const isOccupied = allEntities.value.some(
                        e => e.id !== currentEntityId.value && e.position.x === nx && e.position.y === ny && e.hp > 0
                    );
                    if (!isOccupied) neighbors.push({ x: nx, y: ny });
                }
            }
        }
        return neighbors;
    }

    function findShortestPath(start, target, maxMove) {
        const queue = [[start]];
        const visited = new Set([`${start.x},${start.y}`]);

        while (queue.length > 0) {
            const path = queue.shift();
            const curr = path[path.length - 1];

            if (curr.x === target.x && curr.y === target.y) {
                return path.slice(1);
            }

            if (path.length - 1 < maxMove) {
                for (const n of getNeighbors(curr.x, curr.y)) {
                    const key = `${n.x},${n.y}`;
                    if (!visited.has(key)) {
                        visited.add(key);
                        queue.push([...path, { x: n.x, y: n.y }]);
                    }
                }
            }
        }
        return null;
    }

    // ─── Range calculations ───────────────────────────────────────────────────

    function calculateMoveRange() {
        if (!currentEntity.value) return;
        const start = currentEntity.value.position;
        const maxMove = currentEntity.value.move;
        const queue = [{ x: start.x, y: start.y, dist: 0 }];
        const visited = new Set([`${start.x},${start.y}`]);
        const highlighted = [];

        while (queue.length > 0) {
            const curr = queue.shift();
            if (curr.x !== start.x || curr.y !== start.y) {
                highlighted.push({ x: curr.x, y: curr.y, type: 'move' });
            }
            if (curr.dist < maxMove) {
                for (const n of getNeighbors(curr.x, curr.y)) {
                    const key = `${n.x},${n.y}`;
                    if (!visited.has(key)) {
                        visited.add(key);
                        queue.push({ x: n.x, y: n.y, dist: curr.dist + 1 });
                    }
                }
            }
        }
        highlightedCells.value = highlighted;
    }

    // Highlight full melee ring; enemy validation deferred to handleTileClick.
    function calculateAttackRange() {
        if (!currentEntity.value || !grid.value) return;
        const start = currentEntity.value.position;
        const dirs = [[0, -1], [1, 0], [0, 1], [-1, 0]];
        const highlighted = [];
        for (const d of dirs) {
            const nx = start.x + d[0];
            const ny = start.y + d[1];
            if (nx >= 0 && nx < grid.value.width && ny >= 0 && ny < grid.value.height) {
                const cell = grid.value.cells[nx]?.[ny];
                if (cell && !cell.obstacle) highlighted.push({ x: nx, y: ny, type: 'attack' });
            }
        }
        highlightedCells.value = highlighted;
    }

    // LOS: obstacles and live entities (except caster) block sight.
    function hasLOS(sx, sy, tx, ty) {
        const dx = tx - sx;
        const dy = ty - sy;
        const dist = Math.max(Math.abs(dx), Math.abs(dy));
        if (dist <= 1) return true;
        for (let i = 1; i < dist; i++) {
            const cx = Math.round(sx + dx * i / dist);
            const cy = Math.round(sy + dy * i / dist);
            if (grid.value.cells[cx]?.[cy]?.obstacle) return false;
            if (allEntities.value.some(
                e => e.id !== currentEntityId.value && e.position.x === cx && e.position.y === cy && e.hp > 0
            )) return false;
        }
        return true;
    }

    // Full diamond zone for skill; target-type validation deferred to handleTileClick.
    function calculateSkillRange(skill) {
        if (!currentEntity.value || !grid.value) return;
        const start = currentEntity.value.position;
        const range = skill.targeting?.Range?.value ?? 1;
        const highlighted = [];

        for (let dx = -range; dx <= range; dx++) {
            for (let dy = -range; dy <= range; dy++) {
                if (dx === 0 && dy === 0) continue;
                if (Math.abs(dx) + Math.abs(dy) > range) continue;
                const nx = start.x + dx;
                const ny = start.y + dy;
                if (nx < 0 || nx >= grid.value.width || ny < 0 || ny >= grid.value.height) continue;
                const cell = grid.value.cells[nx]?.[ny];
                if (!cell || cell.obstacle) continue;
                if (!hasLOS(start.x, start.y, nx, ny)) continue;
                highlighted.push({ x: nx, y: ny, type: 'skill' });
            }
        }
        highlightedCells.value = highlighted;
    }

    // ─── handleAction ─────────────────────────────────────────────────────────

    async function handleAction(type) {
        if (!isPlayerTurn.value || isProcessing.value) return;

        // Skill activation
        if (typeof type === 'object' && type.type === 'skill') {
            const skill = type.skill;
            if (!skill || skill.behavior === 'Passive' || skill.behavior === 'Reaction' || skill.behavior === 'Counter') return;

            const targetType = skill.targeting?.TargetType?.value ?? 'Entity';

            if (skill.behavior === 'Direct' && targetType === 'Self') {
                isProcessing.value = true;
                try {
                    await game.sendAction(matchId.value, currentEntityId.value, 'skill', [], { skill_id: skill.skill_id });
                    selectedAction.value = null;
                } catch (err) {
                    console.error('Skill action failed:', err);
                } finally {
                    isProcessing.value = false;
                }
                return;
            }

            selectedAction.value = { type: 'skill', skill };
            calculateSkillRange(skill);
            return;
        }

        if (type === 'pass') {
            isProcessing.value = true;
            try {
                await game.sendAction(matchId.value, currentEntityId.value, 'pass');
                selectedAction.value = null;
                highlightedCells.value = [];
            } catch (err) {
                console.error('Pass action failed:', err);
            } finally {
                isProcessing.value = false;
            }
            return;
        }

        if (type === 'forfeit') {
            showForfeitModal.value = true;
            return;
        }

        selectedAction.value = type;
        selectedPath.value = [];

        if (type === 'move')   calculateMoveRange();
        else if (type === 'attack') calculateAttackRange();
    }

    // ─── handleTileClick ──────────────────────────────────────────────────────

    async function handleTileClick(x, y) {
        if (!isPlayerTurn.value || !selectedAction.value || isProcessing.value) return;

        if (selectedAction.value === 'move') {
            const path = findShortestPath(currentEntity.value.position, { x, y }, currentEntity.value.move);
            if (path) {
                selectedPath.value = path;
                isProcessing.value = true;
                try {
                    await game.sendAction(matchId.value, currentEntityId.value, 'move', path);
                    selectedAction.value = null;
                    highlightedCells.value = [];
                } catch (err) {
                    console.error('Move action failed:', err);
                } finally {
                    isProcessing.value = false;
                }
            }
        } else if (selectedAction.value === 'attack') {
            const inZone = highlightedCells.value.some(c => c.x === x && c.y === y && c.type === 'attack');
            if (inZone) {
                const enemy = enemyEntities.value.find(e => e.position.x === x && e.position.y === y && e.hp > 0);
                if (enemy) {
                    isProcessing.value = true;
                    try {
                        await game.sendAction(matchId.value, currentEntityId.value, 'attack', [{ x, y }]);
                        selectedAction.value = null;
                        highlightedCells.value = [];
                    } catch (err) {
                        console.error('Attack failed:', err);
                    } finally {
                        isProcessing.value = false;
                    }
                }
            }
        } else if (selectedAction.value?.type === 'skill') {
            const inZone = highlightedCells.value.some(c => c.x === x && c.y === y);
            if (inZone) {
                const skill       = selectedAction.value.skill;
                const targetType  = skill.targeting?.TargetType?.value ?? 'Entity';
                const cell        = grid.value.cells[x]?.[y];
                const enemyAtCell = enemyEntities.value.find(e => e.position.x === x && e.position.y === y && e.hp > 0);
                const allyAtCell  = allyEntities.value.find(e => e.id !== currentEntityId.value && e.position.x === x && e.position.y === y && e.hp > 0);
                const anyEntity   = allEntities.value.find(e => e.position.x === x && e.position.y === y && e.hp > 0);

                let valid = false;
                if (skill.behavior === 'Trap')        valid = !cell?.obstacle && !anyEntity;
                else if (targetType === 'EnemyOnly')  valid = !!enemyAtCell;
                else if (targetType === 'FriendOnly') valid = !!allyAtCell;
                else if (targetType === 'Tile')       valid = !cell?.obstacle;
                else                                  valid = !!anyEntity;

                if (valid) {
                    isProcessing.value = true;
                    try {
                        await game.sendAction(matchId.value, currentEntityId.value, 'skill', [{ x, y }], { skill_id: skill.skill_id });
                        selectedAction.value = null;
                        highlightedCells.value = [];
                    } catch (err) {
                        console.error('Skill action failed:', err);
                    } finally {
                        isProcessing.value = false;
                    }
                }
            }
        }
    }

    // ─── Keyboard handler ────────────────────────────────────────────────────

    function handleKeydown(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        if (e.key === 'Escape') {
            selectedAction.value = null;
            selectedPath.value = [];
            highlightedCells.value = [];
            return;
        }
        if (!isPlayerTurn.value || isProcessing.value) return;
        if (e.code === 'KeyM') handleAction('move');
        else if (e.code === 'KeyA') handleAction('attack');
        else if (e.code === 'KeyP' || e.code === 'Space') { e.preventDefault(); handleAction('pass'); }
        else if (e.key >= '1' && e.key <= '5') {
            const idx = parseInt(e.key) - 1;
            const actionable = (currentEntity.value?.equipped_skills ?? []).filter(
                s => s.behavior === 'Direct' || s.behavior === 'Trap',
            );
            if (actionable[idx]) handleAction({ type: 'skill', skill: actionable[idx] });
        }
    }

    // ─── Forfeit ─────────────────────────────────────────────────────────────

    async function executeForfeit() {
        isProcessing.value = true;
        try {
            await game.forfeit(matchId.value);
        } catch (err) {
            console.error('Forfeit failed:', err);
        } finally {
            isProcessing.value = false;
        }
    }

    return {
        selectedAction,
        selectedPath,
        highlightedCells,
        isProcessing,
        showForfeitModal,
        handleAction,
        handleTileClick,
        handleKeydown,
        executeForfeit,
    };
}
