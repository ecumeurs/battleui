<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @spec-link [[req_matchmaking_matchmaking_queue]]
 */
class MatchmakingQueue extends Model
{
    protected $fillable = ['user_id', 'game_mode', 'character_ids'];

    protected $casts = [
        'character_ids' => 'array',
    ];
}
