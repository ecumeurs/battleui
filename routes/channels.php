<?php

use Illuminate\Support\Facades\Broadcast;

// routes/channels.php
Broadcast::channel('arena.{matchId}', function ($user, $matchId) {
    return \App\Models\MatchParticipant::where('match_id', $matchId)
        ->where('player_id', $user->id)
        ->exists();
});