<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @spec-link [[entity_character]]
 * @spec-link [[data_persistence]]
 */
class Character extends Model
{
    use HasUuids;

    protected $fillable = [
        'player_id',
        'name',
        'hp',
        'movement',
        'attack',
        'defense'
    ];

    public function player()
    {
        return $this->belongsTo(User::class, 'player_id');
    }
}
