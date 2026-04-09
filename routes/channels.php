<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

// routes/channels.php
Broadcast::channel('arena.{matchId}', function ($user, $matchId) {
    return \App\Models\MatchParticipant::where('match_id', $matchId)
        ->where('player_id', $user->id)
        ->exists();
});

Broadcast::channel('user.{id}', function ($user, $id) {
    // A user can only subscribe to their own notification channel.
    // id can be UUID or account_name
    return (string) $user->id === (string) $id || $user->account_name === $id;
});