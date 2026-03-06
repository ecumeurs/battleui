<?php

namespace App\Http\Controllers\Battle;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * This Controller stands as a bridge between the Upsilon API and the Battle UI.
 * It will be contacted by Upsilon API via a webhook, and will in turn notify the Frontend via WebSocket.
 */
class BattleController extends Controller
{
    public function webhook(Request $request, string $id)
    {
        // provided id should be a match id. The json body of this webhook is an ArenaEvent.
        // Type of the event will determine course of action for the front. 
        // player_id if set will restrict the message to one player. 
        // winner id (standing of a player id) if set will end the game.
    }
}
