<?php
/*
 * @spec-link [[arch_api_id_masking_gateway]]
 */

namespace App\Policies;

use App\Models\GameMatch;
use App\Models\User;
use App\Models\MatchParticipant;
use Illuminate\Auth\Access\Response;

class GameMatchPolicy
{
    /**
     * Determine whether the user can view the match state.
     * Allowed for participants and administrators.
     */
    public function view(User $user, GameMatch $match): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return MatchParticipant::where('match_id', $match->id)
            ->where('player_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can perform tactical actions in the match.
     * Restricted to actual participants only.
     */
    public function action(User $user, GameMatch $match): bool
    {
        return MatchParticipant::where('match_id', $match->id)
            ->where('player_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can forfeit the match.
     * Restricted to actual participants only.
     */
    public function forfeit(User $user, GameMatch $match): bool
    {
        return $this->action($user, $match);
    }
}
