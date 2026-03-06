<?php

use Illuminate\Support\Facades\Broadcast;

// routes/channels.php
Broadcast::channel('battle.{battleId}', function ($user, $battleId) {
    // TODO: Implement actual authorization logic (check bearer token?)
    return true;
});