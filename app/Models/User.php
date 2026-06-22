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
        'role',
        'ws_channel_key',
        'total_wins',
        'total_losses',
        'ratio',
        'reroll_count',
        'credits',
        'full_address',
        'birth_date'
    ];

    protected $attributes = [
        'credits' => 1000,
        'total_wins' => 0,
        'total_losses' => 0,
        'ratio' => 0.0,
        'reroll_count' => 0,
        'role' => 'Player',
    ];

    protected static function booted()
    {
        static::creating(function ($user) {
            if (!$user->ws_channel_key) {
                $user->ws_channel_key = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

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

    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }

    public function anonymize()
    {
        $this->update([
            'email' => 'anonymized_' . $this->id . '@example.com',
            'full_address' => 'ANONYMIZED',
            'birth_date' => '1900-01-01',
        ]);
        
        $this->delete(); // Ensure soft-delete is triggered if not already
    }

    public function characters()
    {
        return $this->hasMany(Character::class, 'player_id');
    }

    /**
     * @spec-link [[entity_player_inventory]]
     */
    public function inventory()
    {
        return $this->hasMany(PlayerInventory::class, 'player_id');
    }

    /**
     * @spec-link [[entity_player_inventory]]
     */
    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'player_id');
    }
}
