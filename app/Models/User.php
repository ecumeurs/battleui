<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @spec-link [[entity_player]]
 * @spec-link [[data_persistence]]
 */
class User extends Authenticatable
{
    use HasUuids;

    protected $fillable = [
        'account_name',
        'email',
        'password_hash',
        'total_wins',
        'total_losses',
        'ratio'
    ];

    public function characters()
    {
        return $this->hasMany(Character::class, 'player_id');
    }
}
