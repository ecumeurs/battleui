<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Laravel\Sanctum\HasApiTokens;

/**
 * @spec-link [[entity_player]]
 * @spec-link [[data_persistence]]
 */
class User extends Authenticatable
{
    use HasApiTokens, HasUuids;

    protected $fillable = [
        'account_name',
        'email',
        'password_hash',
        'total_wins',
        'total_losses',
        'ratio',
        'reroll_count'
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    public function characters()
    {
        return $this->hasMany(Character::class, 'player_id');
    }
}
