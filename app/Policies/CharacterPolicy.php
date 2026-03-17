<?php

namespace App\Policies;

use App\Models\Character;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CharacterPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Character $character): bool
    {
        return $user->id === $character->player_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Character $character): bool
    {
        return $user->id === $character->player_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Character $character): bool
    {
        return $user->id === $character->player_id;
    }

    /**
     * Determine whether the user can reroll the character stats.
     */
    public function reroll(User $user, Character $character): Response
    {
        if ($user->id !== $character->player_id) {
            return Response::deny('You do not own this character.');
        }

        if ($user->reroll_count >= 3) {
            return Response::deny('Reroll limit reached.');
        }

        return Response::allow();
    }
}
