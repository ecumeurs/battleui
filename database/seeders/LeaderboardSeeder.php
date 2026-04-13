<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Character;
use App\Models\GameMatch;
use App\Models\MatchParticipant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LeaderboardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Initializing Leaderboard Seeder: Neural Crowding in progress...');

        // 1. Create a pool of users
        $userCount = 200;
        $users = User::factory()->count($userCount)->create();

        $this->command->info("Generated {$userCount} survivors.");

        // 2. Generate rosters for each survivor
        $users->each(function (User $user) {
            Character::generateInitialRoster($user->id);
        });

        $this->command->info('Character rosters initialized.');

        // 3. Define the current week window
        $now = Carbon::now('UTC');
        $weekStart = $now->copy()->startOfWeek(Carbon::SUNDAY)->startOfDay()->addMinute();
        
        $modes = ['1v1_PVP', '2v2_PVP', '1v1_PVE', '2v2_PVE'];
        $matchesPerMode = 100;

        foreach ($modes as $mode) {
            $this->command->info("Simulating matches for mode: {$mode}");
            
            for ($i = 0; $i < $matchesPerMode; $i++) {
                // Determine participant count based on mode
                $participantCount = str_contains($mode, '1v1') ? 2 : 4;
                $isPVE = str_contains($mode, 'PVE');

                $matchParticipants = $users->random($participantCount);
                
                // Create the match
                $match = GameMatch::create([
                    'id' => (string) Str::uuid(),
                    'game_mode' => $mode,
                    'concluded_at' => $weekStart->copy()->addMinutes(rand(1, 10000)), // Random time in the week
                    'winning_team_id' => rand(1, 2)
                ]);

                // Create participants
                foreach ($matchParticipants as $index => $u) {
                    // Logic: First half of participants are Team 1, second half are Team 2
                    $team = ($index < ($participantCount / 2)) ? 1 : 2;
                    
                    MatchParticipant::create([
                        'match_id' => $match->id,
                        'player_id' => $u->id,
                        'team' => $team
                    ]);
                }
            }
        }

        $this->command->info('Leaderboard population complete. AREA SECURED.');
    }
}
