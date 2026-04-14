<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @spec-link [[entity_game_match]]
 * @spec-link [[data_persistence]]
 */
class GameMatch extends Model
{
    use HasUuids;

    protected $fillable = [
        'game_state_cache',
        'grid_cache',
        'turn',
        'started_at',
        'concluded_at',
        'winning_team_id',
        'game_mode'
    ];

    protected $casts = [
        'game_state_cache' => 'array',
        'grid_cache' => 'array',
        'started_at' => 'datetime',
        'concluded_at' => 'datetime',
    ];

    public function participants()
    {
        return $this->hasMany(MatchParticipant::class, 'match_id');
    }
}
