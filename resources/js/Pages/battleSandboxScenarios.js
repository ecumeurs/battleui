// Battle Arena Sandbox — curated game-state fixtures for UI/logic testing.
// Each scenario has: label, description, steps[]. A step is a full gameState
// object matching BoardStateResource shape. Use gameStateFactory() to build one.

function makeGrid(w = 6, h = 6, { obstacles = [], elevations = {} } = {}) {
    const cells = {};
    for (let x = 0; x < w; x++) {
        cells[x] = {};
        for (let y = 0; y < h; y++) {
            cells[x][y] = {
                height: elevations[`${x},${y}`] ?? 0,
                obstacle: obstacles.some(o => o[0] === x && o[1] === y),
            };
        }
    }
    return { width: w, height: h, cells };
}

function makeEntity(id, name, x, y, {
    hp = 80, maxHp = 80, move = 3, maxMove = 3,
    attack = 12, defense = 6, facing = 'Down',
    team = 1, playerId = 'p1', nickname = null,
    isSelf = false, equippedSkills = [],
} = {}) {
    return {
        id,
        name,
        nickname: nickname ?? name,
        position: { x, y },
        hp, max_hp: maxHp,
        move, max_move: maxMove,
        attack, defense,
        facing,
        team,
        player_id: playerId,
        is_self: isSelf,
        dead: hp <= 0,
        equipped_skills: equippedSkills,
    };
}

// Skill helpers
function makeSkillDirect(id, name, range = 1, targetType = 'EnemyOnly', tags = ['melee']) {
    return {
        skill_id: id,
        name,
        behavior: 'Direct',
        tags,
        costs: {},
        targeting: {
            Range: { value: range },
            TargetType: { value: targetType },
            TargetingMechanics: { value: 'Anywhere' },
            Zone: { pattern: [], patternType: 'Single' },
        },
    };
}

function makeSkillSelf(id, name, tags = ['buff']) {
    return {
        skill_id: id,
        name,
        behavior: 'Direct',
        tags,
        costs: {},
        targeting: {
            Range: { value: 0 },
            TargetType: { value: 'Self' },
            TargetingMechanics: { value: 'Anywhere' },
            Zone: { pattern: [], patternType: 'Single' },
        },
    };
}

function makeSkillPassive(id, name, tags = ['buff']) {
    return { skill_id: id, name, behavior: 'Passive', tags, costs: {}, targeting: {} };
}

function makeSkillReactive(id, name, tags = ['reaction']) {
    return { skill_id: id, name, behavior: 'Reaction', tags, costs: {}, targeting: {} };
}

function makeSkillCounter(id, name, tags = ['counter']) {
    return { skill_id: id, name, behavior: 'Counter', tags, costs: {}, targeting: {} };
}

function makeSkillTrap(id, name, range = 2, tags = ['trap']) {
    return {
        skill_id: id,
        name,
        behavior: 'Trap',
        tags,
        costs: {},
        targeting: {
            Range: { value: range },
            TargetType: { value: 'Tile' },
            TargetingMechanics: { value: 'Anywhere' },
            Zone: { pattern: [], patternType: 'Single' },
        },
    };
}

// Full game state factory
function makeState({
    version = 1,
    grid = makeGrid(),
    players = [],
    turn = [],
    currentEntityId = '',
    currentPlayerIsSelf = false,
    timeout = null,
    action = null,
    gameFinished = false,
    winnerTeamId = null,
} = {}) {
    return {
        version,
        grid,
        players,
        turn,
        current_entity_id: currentEntityId,
        current_player_is_self: currentPlayerIsSelf,
        timeout,
        action,
        game_finished: gameFinished,
        winner_team_id: winnerTeamId,
    };
}

// ─── Common test entities ───────────────────────────────────────────────────

const SELF_ENTITY = makeEntity('e1', 'Kairo', 1, 1, {
    hp: 80, maxHp: 80, isSelf: true, team: 1, playerId: 'p1',
});
const ALLY_ENTITY = makeEntity('e2', 'Lyra', 3, 1, {
    hp: 60, maxHp: 70, isSelf: false, team: 1, playerId: 'p2',
});
const ENEMY_ENTITY = makeEntity('e3', 'Grim', 4, 4, {
    hp: 90, maxHp: 90, isSelf: false, team: 2, playerId: 'pEnemy',
});

const BASE_PLAYERS = [
    { player_id: 'p1', nickname: 'Player1', team: 1, is_self: true,  entities: [SELF_ENTITY] },
    { player_id: 'p2', nickname: 'Ally',    team: 1, is_self: false, entities: [ALLY_ENTITY] },
    { player_id: 'pEnemy', nickname: 'Enemy', team: 2, is_self: false, entities: [ENEMY_ENTITY] },
];

const BASE_TURN = [
    { entity_id: 'e1', delay: 10, is_self: true,  team: 1 },
    { entity_id: 'e2', delay: 20, is_self: false, team: 1 },
    { entity_id: 'e3', delay: 35, is_self: false, team: 2 },
];

// ─── Scenario definitions ───────────────────────────────────────────────────

export const SCENARIOS = {

    'damage-hp-update': {
        label: 'HP Update on Damage',
        description: 'Enemy attacks; HP bars update on board, roster header, and initiative token.',
        steps: [
            makeState({
                version: 1,
                grid: makeGrid(6, 6),
                players: [
                    { player_id: 'p1', nickname: 'Player1', team: 1, is_self: true,  entities: [makeEntity('e1', 'Kairo', 2, 2, { hp: 80, maxHp: 80, isSelf: true, team: 1, playerId: 'p1' })] },
                    { player_id: 'pEnemy', nickname: 'Enemy', team: 2, is_self: false, entities: [makeEntity('e3', 'Grim', 3, 2, { hp: 90, maxHp: 90, team: 2, playerId: 'pEnemy' })] },
                ],
                turn: [
                    { entity_id: 'e1', delay: 10, is_self: true,  team: 1 },
                    { entity_id: 'e3', delay: 20, is_self: false, team: 2 },
                ],
                currentEntityId: 'e1',
                currentPlayerIsSelf: true,
            }),
            // After attack — Kairo HP dropped from 80 to 50
            makeState({
                version: 2,
                grid: makeGrid(6, 6),
                players: [
                    { player_id: 'p1', nickname: 'Player1', team: 1, is_self: true,  entities: [makeEntity('e1', 'Kairo', 2, 2, { hp: 50, maxHp: 80, isSelf: true, team: 1, playerId: 'p1' })] },
                    { player_id: 'pEnemy', nickname: 'Enemy', team: 2, is_self: false, entities: [makeEntity('e3', 'Grim', 3, 2, { hp: 90, maxHp: 90, team: 2, playerId: 'pEnemy' })] },
                ],
                turn: [
                    { entity_id: 'e3', delay: 10, is_self: false, team: 2 },
                    { entity_id: 'e1', delay: 25, is_self: true,  team: 1 },
                ],
                currentEntityId: 'e3',
                currentPlayerIsSelf: false,
                action: { type: 'attack', damage: 30, prev_hp: 80, new_hp: 50 },
            }),
        ],
    },

    'character-death': {
        label: 'Character Death',
        description: 'Character HP reaches 0 — pawn disappears from board and initiative timeline.',
        steps: [
            makeState({
                version: 1,
                grid: makeGrid(6, 6),
                players: [
                    { player_id: 'p1', nickname: 'Player1', team: 1, is_self: true,  entities: [makeEntity('e1', 'Kairo', 2, 2, { hp: 80, maxHp: 80, isSelf: true, team: 1, playerId: 'p1' })] },
                    { player_id: 'pEnemy', nickname: 'Enemy', team: 2, is_self: false, entities: [makeEntity('e3', 'Grim', 3, 2, { hp: 10, maxHp: 90, team: 2, playerId: 'pEnemy' })] },
                ],
                turn: [
                    { entity_id: 'e1', delay: 5, is_self: true,  team: 1 },
                    { entity_id: 'e3', delay: 20, is_self: false, team: 2 },
                ],
                currentEntityId: 'e1',
                currentPlayerIsSelf: true,
            }),
            // Enemy dies
            makeState({
                version: 2,
                grid: makeGrid(6, 6),
                players: [
                    { player_id: 'p1', nickname: 'Player1', team: 1, is_self: true,  entities: [makeEntity('e1', 'Kairo', 2, 2, { hp: 80, maxHp: 80, isSelf: true, team: 1, playerId: 'p1' })] },
                    { player_id: 'pEnemy', nickname: 'Enemy', team: 2, is_self: false, entities: [makeEntity('e3', 'Grim', 3, 2, { hp: 0, maxHp: 90, team: 2, playerId: 'pEnemy' })] },
                ],
                turn: [
                    { entity_id: 'e1', delay: 20, is_self: true, team: 1 },
                    // e3 removed from turn order (dead)
                ],
                currentEntityId: 'e1',
                currentPlayerIsSelf: true,
                action: { type: 'attack', damage: 10, prev_hp: 10, new_hp: 0 },
            }),
        ],
    },

    'movement-sequence': {
        label: 'Movement',
        description: 'Character moves across the board — position updates on roster and 3D board.',
        steps: [
            makeState({
                version: 1,
                grid: makeGrid(6, 6),
                players: [
                    { player_id: 'p1', nickname: 'Player1', team: 1, is_self: true, entities: [makeEntity('e1', 'Kairo', 1, 1, { hp: 80, maxHp: 80, isSelf: true, move: 3, maxMove: 3, team: 1, playerId: 'p1' })] },
                    { player_id: 'pEnemy', nickname: 'Enemy', team: 2, is_self: false, entities: [makeEntity('e3', 'Grim', 4, 4, { hp: 90, maxHp: 90, team: 2, playerId: 'pEnemy' })] },
                ],
                turn: [{ entity_id: 'e1', delay: 10, is_self: true, team: 1 }],
                currentEntityId: 'e1',
                currentPlayerIsSelf: true,
            }),
            // After move: Kairo at (3,3)
            makeState({
                version: 2,
                grid: makeGrid(6, 6),
                players: [
                    { player_id: 'p1', nickname: 'Player1', team: 1, is_self: true, entities: [makeEntity('e1', 'Kairo', 3, 3, { hp: 80, maxHp: 80, isSelf: true, move: 0, maxMove: 3, team: 1, playerId: 'p1' })] },
                    { player_id: 'pEnemy', nickname: 'Enemy', team: 2, is_self: false, entities: [makeEntity('e3', 'Grim', 4, 4, { hp: 90, maxHp: 90, team: 2, playerId: 'pEnemy' })] },
                ],
                turn: [{ entity_id: 'e1', delay: 15, is_self: true, team: 1 }],
                currentEntityId: 'e1',
                currentPlayerIsSelf: true,
                action: { type: 'move', path: [{ x:1,y:1 },{ x:2,y:2 },{ x:3,y:3 }] },
            }),
        ],
    },

    'skill-direct-range': {
        label: 'Skill: Direct (Melee Range)',
        description: 'Direct skill enters targeting mode — enemy tiles within range 1 are highlighted gold.',
        steps: [
            makeState({
                version: 1,
                grid: makeGrid(6, 6),
                players: [
                    {
                        player_id: 'p1', nickname: 'Player1', team: 1, is_self: true,
                        entities: [makeEntity('e1', 'Kairo', 2, 2, {
                            hp: 80, maxHp: 80, isSelf: true, team: 1, playerId: 'p1',
                            equippedSkills: [makeSkillDirect('sk1', 'Slash', 1, 'EnemyOnly', ['melee'])],
                        })],
                    },
                    { player_id: 'pEnemy', nickname: 'Enemy', team: 2, is_self: false, entities: [makeEntity('e3', 'Grim', 3, 2, { hp: 90, maxHp: 90, team: 2, playerId: 'pEnemy' })] },
                ],
                turn: [
                    { entity_id: 'e1', delay: 5,  is_self: true,  team: 1 },
                    { entity_id: 'e3', delay: 20, is_self: false, team: 2 },
                ],
                currentEntityId: 'e1',
                currentPlayerIsSelf: true,
            }),
        ],
    },

    'skill-direct-ranged': {
        label: 'Skill: Direct (Ranged, Range 3)',
        description: 'Ranged Direct skill highlights enemy tiles up to 3 tiles away.',
        steps: [
            makeState({
                version: 1,
                grid: makeGrid(7, 7),
                players: [
                    {
                        player_id: 'p1', nickname: 'Player1', team: 1, is_self: true,
                        entities: [makeEntity('e1', 'Kairo', 1, 3, {
                            hp: 80, maxHp: 80, isSelf: true, team: 1, playerId: 'p1',
                            equippedSkills: [makeSkillDirect('sk2', 'Arrow Shot', 3, 'EnemyOnly', ['ranged'])],
                        })],
                    },
                    { player_id: 'pEnemy', nickname: 'Enemy', team: 2, is_self: false, entities: [makeEntity('e3', 'Grim', 4, 3, { hp: 90, maxHp: 90, team: 2, playerId: 'pEnemy' })] },
                ],
                turn: [
                    { entity_id: 'e1', delay: 5,  is_self: true,  team: 1 },
                    { entity_id: 'e3', delay: 20, is_self: false, team: 2 },
                ],
                currentEntityId: 'e1',
                currentPlayerIsSelf: true,
            }),
        ],
    },

    'skill-self-instant': {
        label: 'Skill: Self-Targeting (Instant Fire)',
        description: 'Direct + TargetType=Self skill fires without entering targeting mode.',
        steps: [
            makeState({
                version: 1,
                grid: makeGrid(6, 6),
                players: [
                    {
                        player_id: 'p1', nickname: 'Player1', team: 1, is_self: true,
                        entities: [makeEntity('e1', 'Kairo', 2, 2, {
                            hp: 60, maxHp: 80, isSelf: true, team: 1, playerId: 'p1',
                            equippedSkills: [makeSkillSelf('sk3', 'Iron Will', ['shield', 'buff'])],
                        })],
                    },
                    { player_id: 'pEnemy', nickname: 'Enemy', team: 2, is_self: false, entities: [makeEntity('e3', 'Grim', 5, 5, { hp: 90, maxHp: 90, team: 2, playerId: 'pEnemy' })] },
                ],
                turn: [
                    { entity_id: 'e1', delay: 5,  is_self: true,  team: 1 },
                    { entity_id: 'e3', delay: 20, is_self: false, team: 2 },
                ],
                currentEntityId: 'e1',
                currentPlayerIsSelf: true,
            }),
        ],
    },

    'skill-passive-display': {
        label: 'Skill: Passive (Display Only)',
        description: 'Passive skill appears in the rail section of ActionPanel — clicking does nothing.',
        steps: [
            makeState({
                version: 1,
                grid: makeGrid(6, 6),
                players: [
                    {
                        player_id: 'p1', nickname: 'Player1', team: 1, is_self: true,
                        entities: [makeEntity('e1', 'Kairo', 2, 2, {
                            hp: 80, maxHp: 80, isSelf: true, team: 1, playerId: 'p1',
                            equippedSkills: [
                                makeSkillDirect('sk1', 'Slash', 1, 'EnemyOnly', ['melee']),
                                makeSkillPassive('sk4', 'Stoic Shield', ['shield', 'buff']),
                            ],
                        })],
                    },
                    { player_id: 'pEnemy', nickname: 'Enemy', team: 2, is_self: false, entities: [makeEntity('e3', 'Grim', 4, 4, { hp: 90, maxHp: 90, team: 2, playerId: 'pEnemy' })] },
                ],
                turn: [
                    { entity_id: 'e1', delay: 5,  is_self: true,  team: 1 },
                    { entity_id: 'e3', delay: 20, is_self: false, team: 2 },
                ],
                currentEntityId: 'e1',
                currentPlayerIsSelf: true,
            }),
        ],
    },

    'skill-reactive-display': {
        label: 'Skill: Reaction + Counter (Display Only)',
        description: 'Reactive and counter skills appear in ActionPanel — they auto-trigger, clicking should be a no-op.',
        steps: [
            makeState({
                version: 1,
                grid: makeGrid(6, 6),
                players: [
                    {
                        player_id: 'p1', nickname: 'Player1', team: 1, is_self: true,
                        entities: [makeEntity('e1', 'Kairo', 2, 2, {
                            hp: 80, maxHp: 80, isSelf: true, team: 1, playerId: 'p1',
                            equippedSkills: [
                                makeSkillDirect('sk1', 'Slash', 1, 'EnemyOnly', ['melee']),
                                makeSkillReactive('sk5', 'Parry', ['reaction', 'shield']),
                                makeSkillCounter('sk6', 'Riposte', ['counter', 'melee']),
                            ],
                        })],
                    },
                    { player_id: 'pEnemy', nickname: 'Enemy', team: 2, is_self: false, entities: [makeEntity('e3', 'Grim', 4, 4, { hp: 90, maxHp: 90, team: 2, playerId: 'pEnemy' })] },
                ],
                turn: [
                    { entity_id: 'e1', delay: 5,  is_self: true,  team: 1 },
                    { entity_id: 'e3', delay: 20, is_self: false, team: 2 },
                ],
                currentEntityId: 'e1',
                currentPlayerIsSelf: true,
            }),
        ],
    },

    'skill-trap': {
        label: 'Skill: Trap (Tile Placement)',
        description: 'Trap skill highlights empty walkable tiles in range for placement.',
        steps: [
            makeState({
                version: 1,
                grid: makeGrid(6, 6),
                players: [
                    {
                        player_id: 'p1', nickname: 'Player1', team: 1, is_self: true,
                        entities: [makeEntity('e1', 'Kairo', 1, 1, {
                            hp: 80, maxHp: 80, isSelf: true, team: 1, playerId: 'p1',
                            equippedSkills: [makeSkillTrap('sk7', 'Landmine', 2, ['trap'])],
                        })],
                    },
                    { player_id: 'pEnemy', nickname: 'Enemy', team: 2, is_self: false, entities: [makeEntity('e3', 'Grim', 4, 4, { hp: 90, maxHp: 90, team: 2, playerId: 'pEnemy' })] },
                ],
                turn: [
                    { entity_id: 'e1', delay: 5,  is_self: true,  team: 1 },
                    { entity_id: 'e3', delay: 20, is_self: false, team: 2 },
                ],
                currentEntityId: 'e1',
                currentPlayerIsSelf: true,
            }),
        ],
    },

    'skill-aoe': {
        label: 'Skill: Direct AoE',
        description: 'AoE Direct skill highlights a zone of tiles after target selection.',
        steps: [
            makeState({
                version: 1,
                grid: makeGrid(7, 7),
                players: [
                    {
                        player_id: 'p1', nickname: 'Player1', team: 1, is_self: true,
                        entities: [makeEntity('e1', 'Kairo', 1, 3, {
                            hp: 80, maxHp: 80, isSelf: true, team: 1, playerId: 'p1',
                            equippedSkills: [
                                {
                                    skill_id: 'sk8', name: 'Shockwave', behavior: 'Direct',
                                    tags: ['aoe', 'melee'], costs: {},
                                    targeting: {
                                        Range: { value: 2 },
                                        TargetType: { value: 'EntityOrTile' },
                                        TargetingMechanics: { value: 'Anywhere' },
                                        Zone: { pattern: [[0,0],[1,0],[-1,0],[0,1],[0,-1]], patternType: 'Cross' },
                                    },
                                },
                            ],
                        })],
                    },
                    { player_id: 'pEnemy', nickname: 'Enemy', team: 2, is_self: false, entities: [
                        makeEntity('e3', 'Grim', 4, 3, { hp: 90, maxHp: 90, team: 2, playerId: 'pEnemy' }),
                        makeEntity('e4', 'Vex',  4, 4, { hp: 70, maxHp: 70, team: 2, playerId: 'pEnemy' }),
                    ]},
                ],
                turn: [
                    { entity_id: 'e1', delay: 5,  is_self: true,  team: 1 },
                    { entity_id: 'e3', delay: 20, is_self: false, team: 2 },
                    { entity_id: 'e4', delay: 25, is_self: false, team: 2 },
                ],
                currentEntityId: 'e1',
                currentPlayerIsSelf: true,
            }),
        ],
    },

    'turn-transition': {
        label: 'Turn Transition',
        description: 'Active entity changes — ActionPanel switches ownership and timeline token updates.',
        steps: [
            makeState({
                version: 1,
                grid: makeGrid(6, 6),
                players: BASE_PLAYERS,
                turn: BASE_TURN,
                currentEntityId: 'e1',
                currentPlayerIsSelf: true,
            }),
            makeState({
                version: 2,
                grid: makeGrid(6, 6),
                players: BASE_PLAYERS,
                turn: [
                    { entity_id: 'e3', delay: 10, is_self: false, team: 2 },
                    { entity_id: 'e2', delay: 20, is_self: false, team: 1 },
                    { entity_id: 'e1', delay: 35, is_self: true,  team: 1 },
                ],
                currentEntityId: 'e3',
                currentPlayerIsSelf: false,
                action: { type: 'pass' },
            }),
        ],
    },

    'game-end-win': {
        label: 'Game End: Victory',
        description: 'game_finished=true with self team winning — victory overlay should appear.',
        steps: [
            makeState({
                version: 5,
                grid: makeGrid(6, 6),
                players: [
                    { player_id: 'p1', nickname: 'Player1', team: 1, is_self: true, entities: [makeEntity('e1', 'Kairo', 2, 2, { hp: 45, maxHp: 80, isSelf: true, team: 1, playerId: 'p1' })] },
                    { player_id: 'pEnemy', nickname: 'Enemy', team: 2, is_self: false, entities: [makeEntity('e3', 'Grim', 4, 4, { hp: 0, maxHp: 90, team: 2, playerId: 'pEnemy' })] },
                ],
                turn: [],
                currentEntityId: '',
                currentPlayerIsSelf: false,
                gameFinished: true,
                winnerTeamId: 1,
            }),
        ],
    },

    'game-end-loss': {
        label: 'Game End: Defeat',
        description: 'game_finished=true with enemy team winning — defeat overlay should appear.',
        steps: [
            makeState({
                version: 5,
                grid: makeGrid(6, 6),
                players: [
                    { player_id: 'p1', nickname: 'Player1', team: 1, is_self: true, entities: [makeEntity('e1', 'Kairo', 2, 2, { hp: 0, maxHp: 80, isSelf: true, team: 1, playerId: 'p1' })] },
                    { player_id: 'pEnemy', nickname: 'Enemy', team: 2, is_self: false, entities: [makeEntity('e3', 'Grim', 4, 4, { hp: 55, maxHp: 90, team: 2, playerId: 'pEnemy' })] },
                ],
                turn: [],
                currentEntityId: '',
                currentPlayerIsSelf: false,
                gameFinished: true,
                winnerTeamId: 2,
            }),
        ],
    },
};

export const SCENARIO_LIST = Object.entries(SCENARIOS).map(([key, s]) => ({
    key,
    label: s.label,
    description: s.description,
    steps: s.steps.length,
}));
