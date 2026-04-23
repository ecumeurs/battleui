<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @spec-link [[entity_character]]
 * @spec-link [[data_persistence]]
 */
class Character extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'player_id',
        'name',
        'hp',
        'movement',
        'attack',
        'defense',
        'initial_movement',
        'spent_cp'
    ];

    public function player()
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    /**
     * @spec-link [[rule_character_create_character]]
     * @spec-link [[entity_character_allocate_hp]]
     * @spec-link [[uc_player_registration_generate_characters]]
     */
    public static function generateInitialRoster(string $playerId)
    {
        for ($i = 0; $i < 3; $i++) {
            $stats = self::getBaseStats();
            self::create([
                'player_id' => $playerId,
                'name' => "Character " . ($i + 1),
                'hp' => $stats['hp'],
                'movement' => $stats['movement'],
                'attack' => $stats['attack'],
                'defense' => $stats['defense'],
                'initial_movement' => $stats['movement'],
                'spent_cp' => 0,
            ]);
        }
    }

    /**
     * @spec-link [[mech_character_reroll]]
     * @spec-link [[mech_character_reroll_effect]]
     */
    public function rerollStats()
    {
        $stats = self::getBaseStats();
        $this->update([
            'hp' => $stats['hp'],
            'movement' => $stats['movement'],
            'attack' => $stats['attack'],
            'defense' => $stats['defense'],
            'initial_movement' => $stats['movement'],
            'spent_cp' => 0,
        ]);
        return $this;
    }

    /**
     * @spec-link [[rule_character_create_character]]
     */
    public static function getBaseStats(): array
    {
        // V2 Baseline Stats
        return [
            'hp' => 30,
            'attack' => 10,
            'defense' => 5,
            'movement' => 3
        ];
    }

    /**
     * Legacy point distribution for AI and tests.
     * @spec-link [[entity_character_allocate_hp]]
     */
    public static function distributePoints(int $total = 10): array
    {
        $stats = ['hp' => 3, 'movement' => 1, 'attack' => 1, 'defense' => 1];
        $remaining = $total - array_sum($stats);
        
        $keys = array_keys($stats);
        for ($i = 0; $i < $remaining; $i++) {
            $stats[$keys[array_rand($keys)]]++;
        }
        return $stats;
    }
}
