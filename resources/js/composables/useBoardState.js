/**
 * useBoardState — derived board/entity projections from raw gameState.
 *
 * Handles:
 *  - allEntities, turnOrder flattening
 *  - Player identity (myPlayer, currentPlayerId)
 *  - Team separation (ally/enemy participants, entities, rosters)
 *  - Team color mapping
 *  - HP aggregates, match duration, shot clock
 *  - isGameOver / isVictory flags
 */
import { ref, computed } from 'vue';
import { tactical } from '@/services/tactical';
import { TEAM_COLORS } from '@/constants/theme.js';

export function useBoardState(gameState) {
    // ─── Timers ───────────────────────────────────────────────────────────────
    const matchSeconds = ref(0);
    const shotClock = ref(0);

    // ─── Identity ────────────────────────────────────────────────────────────
    const myPlayer = computed(() => tactical.myPlayer(gameState.value));
    const currentPlayerId = computed(() => {
        if (!myPlayer.value) return '';
        return String(myPlayer.value.player_id || myPlayer.value.nickname);
    });

    // ─── Entities ────────────────────────────────────────────────────────────
    const grid = computed(() => gameState.value?.grid || null);
    const allEntities = computed(() => {
        if (!gameState.value?.players) return [];
        return gameState.value.players.flatMap(p => p.entities);
    });

    const turnOrder = computed(() => {
        if (!gameState.value?.turn) return [];
        return gameState.value.turn.map(t => {
            const ent = allEntities.value.find(e => e.id === t.entity_id);
            return { ...t, name: ent ? ent.name : 'Unknown' };
        });
    });

    const currentEntityId = computed(() => gameState.value?.current_entity_id || '');
    const currentEntity = computed(() => tactical.currentCharacter(gameState.value));
    const isPlayerTurn = computed(() => !!gameState.value?.current_player_is_self);
    const activePlayerName = computed(() => tactical.currentPlayer(gameState.value)?.nickname || '');

    // ─── Team grouping ────────────────────────────────────────────────────────
    const myTeam = computed(() => myPlayer.value?.team || 1);

    const allyParticipants = computed(() =>
        tactical.myAllies(gameState.value).concat(myPlayer.value ? [myPlayer.value] : [])
    );
    const enemyParticipants = computed(() => tactical.myFoes(gameState.value));

    const allyEntities = computed(() =>
        tactical.myCharacters(gameState.value).concat(tactical.myAlliesCharacters(gameState.value))
    );
    const enemyEntities = computed(() => tactical.myFoesCharacters(gameState.value));

    // ─── Roster projection ────────────────────────────────────────────────────
    function mapParticipantsToRoster(parts) {
        return parts.map(p => ({
            id: String(p.player_id || p.nickname),
            nickname: p.nickname,
            team: p.team,
            entities: (p.entities || []).map(e => ({
                ...e,
                _isActive: String(e.id) === currentEntityId.value
            }))
        }));
    }

    const allyRoster = computed(() => mapParticipantsToRoster(allyParticipants.value));
    const enemyRoster = computed(() => mapParticipantsToRoster(enemyParticipants.value));

    // ─── Team color map ───────────────────────────────────────────────────────
    const teamColors = computed(() => {
        const colors = {};
        if (!gameState.value?.players) return colors;

        gameState.value.players.forEach(p => {
            let color = '#ffffff';
            if (p.is_self) {
                color = TEAM_COLORS.self;
            } else if (p.team === myTeam.value) {
                color = TEAM_COLORS.ally;
            } else {
                const foes = enemyParticipants.value;
                if (foes[0] && (foes[0].player_id === p.player_id || foes[0].nickname === p.nickname)) {
                    color = TEAM_COLORS.enemy;
                } else {
                    color = TEAM_COLORS.enemy2;
                }
            }

            if (p.player_id) colors[String(p.player_id)] = color;
            if (p.nickname)  colors[String(p.nickname)]  = color;
        });

        return colors;
    });

    // ─── Game state flags ─────────────────────────────────────────────────────
    const isGameOver = computed(() => !!gameState.value?.game_finished);
    const isVictory  = computed(() => tactical.isWinner(gameState.value));

    // ─── HP aggregates ────────────────────────────────────────────────────────
    const allyTeamHp         = computed(() => allyEntities.value.reduce((s, e) => s + e.hp, 0));
    const allyTeamMaxHp      = computed(() => allyEntities.value.reduce((s, e) => s + e.max_hp, 0));
    const allyCharsRemaining  = computed(() => allyEntities.value.filter(e => e.hp > 0).length);

    const enemyTeamHp         = computed(() => enemyEntities.value.reduce((s, e) => s + e.hp, 0));
    const enemyTeamMaxHp      = computed(() => enemyEntities.value.reduce((s, e) => s + e.max_hp, 0));
    const enemyCharsRemaining  = computed(() => enemyEntities.value.filter(e => e.hp > 0).length);

    // ─── Clock formatting ─────────────────────────────────────────────────────
    const matchDuration = computed(() => {
        const m = Math.floor(matchSeconds.value / 60);
        const s = matchSeconds.value % 60;
        return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    });

    // ─── Action capability ────────────────────────────────────────────────────
    const canMove   = computed(() => (currentEntity.value?.move ?? 0) > 0);
    const canAttack = computed(() => (currentEntity.value?.hp ?? 0) > 0);

    return {
        // Timers (mutable refs for external setInterval)
        matchSeconds,
        shotClock,

        // Identity
        myPlayer,
        currentPlayerId,

        // Grid + entities
        grid,
        allEntities,
        turnOrder,
        currentEntityId,
        currentEntity,
        isPlayerTurn,
        activePlayerName,

        // Team grouping
        myTeam,
        allyParticipants,
        enemyParticipants,
        allyEntities,
        enemyEntities,

        // Rosters
        allyRoster,
        enemyRoster,

        // Colors
        teamColors,

        // Flags
        isGameOver,
        isVictory,

        // HP aggregates
        allyTeamHp,
        allyTeamMaxHp,
        allyCharsRemaining,
        enemyTeamHp,
        enemyTeamMaxHp,
        enemyCharsRemaining,

        // Formatted clock
        matchDuration,

        // Action eligibility
        canMove,
        canAttack,
    };
}
