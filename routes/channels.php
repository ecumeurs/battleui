<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

// routes/channels.php
Broadcast::channel('arena.{matchId}', function ($user, $matchId) {
    return \App\Models\MatchParticipant::where('match_id', $matchId)
        ->where('player_id', $user->id)
        ->exists();
});

Broadcast::channel('user.{key}', function ($user, $key) {
    // A user can only subscribe to their own notification channel using their private key.
    return $user->ws_channel_key === $key;
});