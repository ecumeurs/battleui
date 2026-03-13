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

    /**
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
        ]);
        return $this;
    }

    public static function distributePoints(int $totalPoints): array
    {
        $stats = ['hp' => 3, 'movement' => 1, 'attack' => 1, 'defense' => 1];
        $remaining = $totalPoints - (3 + 1 + 1 + 1); // Minimum values

        $keys = array_keys($stats);
        while ($remaining > 0) {
            $key = $keys[array_rand($keys)];
            $stats[$key]++;
            $remaining--;
        }

        return $stats;
    }
}
