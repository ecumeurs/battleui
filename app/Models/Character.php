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
        'defense',
        'initial_movement'
    ];

    public function player()
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    /**
     * @spec-link [[entity_character_allocate_hp]]
     * @spec-link [[uc_player_registration_generate_characters]]
     */
    public static function generateInitialRoster(string $playerId)
    {
        for ($i = 0; $i < 3; $i++) {
            $stats = self::distributePoints(10);
            self::create([
                'player_id' => $playerId,
                'name' => "Character " . ($i + 1),
                'hp' => $stats['hp'],
                'movement' => $stats['movement'],
                'attack' => $stats['attack'],
                'defense' => $stats['defense'],
                'initial_movement' => $stats['movement'],
            ]);
        }
    }

    /**
     * @spec-link [[mech_character_reroll]]
     */
    public function rerollStats()
    {
        $stats = self::distributePoints(10);
        $this->update([
            'hp' => $stats['hp'],
            'movement' => $stats['movement'],
            'attack' => $stats['attack'],
            'defense' => $stats['defense'],
            'initial_movement' => $stats['movement'],
        ]);
        return $this;
    }

    /**
     * @spec-link [[entity_character_allocate_hp]]
     */
    public static function distributePoints(int $totalPoints): array
    {
        // New Rule: 3 HP, 1 in others (Total 6 base)
        $stats = ['hp' => 3, 'movement' => 1, 'attack' => 1, 'defense' => 1];
        
        // Exactly 4 points dispatched
        $remaining = 4; // $totalPoints - (3 + 1 + 1 + 1) where $totalPoints is 10

        $keys = array_keys($stats);
        while ($remaining > 0) {
            $key = $keys[array_rand($keys)];
            $stats[$key]++;
            $remaining--;
        }

        return $stats;
    }
}
