<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchParticipant extends Model
{
    protected $fillable = [
        'match_id',
        'player_id',
        'team',
        'status'
    ];

    public function match()
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function player()
    {
        return $this->belongsTo(User::class, 'player_id');
    }
}
