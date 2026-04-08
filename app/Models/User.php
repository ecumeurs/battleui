<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @spec-link [[entity_player]]
 * @spec-link [[data_persistence]]
 */
class User extends Authenticatable
{
    use HasApiTokens, HasUuids, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'account_name',
        'email',
        'password_hash',
        'total_wins',
        'total_losses',
        'ratio',
        'reroll_count',
        'full_address',
        'birth_date'
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    public function anonymize()
    {
        $this->update([
            'full_address' => 'ANONYMIZED',
            'birth_date' => '1900-01-01',
        ]);
    }

    public function characters()
    {
        return $this->hasMany(Character::class, 'player_id');
    }
}
