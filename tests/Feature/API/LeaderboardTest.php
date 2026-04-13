<?php

namespace Tests\Feature\API;

use App\Models\GameMatch;
use App\Models\MatchParticipant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @spec-link [[api_leaderboard]]
 * @spec-link [[rule_leaderboard_score_calculation]]
 * @spec-link [[rule_leaderboard_cycle]]
 */
class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;
    protected User $user2;
    protected User $user3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::create([
            'account_name' => 'TopPlayer',
            'email' => 'top@example.com',
            'password_hash' => bcrypt('password'),
        ]);

        $this->user2 = User::create([
            'account_name' => 'MidPlayer',
            'email' => 'mid@example.com',
            'password_hash' => bcrypt('password'),
        ]);

        $this->user3 = User::create([
            'account_name' => 'OldPlayer',
            'email' => 'old@example.com',
            'password_hash' => bcrypt('password'),
        ]);
    }

    public function test_leaderboard_filters_by_current_week()
    {
        $now = now('UTC');
        // Sunday 00:01 UTC
        $thisWeekStart = $now->copy()->startOfWeek(Carbon::SUNDAY)->addMinute();
        $lastWeek = $thisWeekStart->copy()->subDays(2);

        // Match 1: This week, user1 wins
        $match1 = GameMatch::create([
            'game_mode' => '1v1_PVP',
            'concluded_at' => $thisWeekStart->copy()->addHour(),
            'winning_team_id' => 1
        ]);
        MatchParticipant::create(['match_id' => $match1->id, 'player_id' => $this->user1->id, 'team' => 1]);
        MatchParticipant::create(['match_id' => $match1->id, 'player_id' => $this->user2->id, 'team' => 2]);

        // Match 2: Last week, user3 wins (should be filtered out)
        $match2 = GameMatch::create([
            'game_mode' => '1v1_PVP',
            'concluded_at' => $lastWeek,
            'winning_team_id' => 1
        ]);
        MatchParticipant::create(['match_id' => $match2->id, 'player_id' => $this->user3->id, 'team' => 1]);

        $response = $this->actingAs($this->user1)
            ->getJson('/api/v1/leaderboard?mode=1v1_PVP');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'results' => [
                        [
                            'account_name' => 'TopPlayer',
                            'wins' => 1,
                            'losses' => 0,
                            'score' => 1.0,
                            'is_self' => true
                        ]
                    ]
                ]
            ]);

        // Ensure user3 (OldPlayer) is not in the results, but both TopPlayer and MidPlayer are
        $results = $response->json('data.results');
        $names = collect($results)->pluck('account_name')->toArray();
        
        $this->assertCount(2, $results, "Expected 2 users, found: " . implode(', ', $names));
        $this->assertContains('TopPlayer', $names);
        $this->assertContains('MidPlayer', $names);
        $this->assertNotContains('OldPlayer', $names);
    }

    public function test_score_calculation_logic()
    {
        $thisWeek = now('UTC')->startOfWeek(Carbon::SUNDAY)->addHour();

        // user1: 10 wins, 0 losses -> 10 / max(1, 0) = 10
        for ($i = 0; $i < 10; $i++) {
            $m = GameMatch::create(['game_mode' => '1v1_PVP', 'concluded_at' => $thisWeek, 'winning_team_id' => 1]);
            MatchParticipant::create(['match_id' => $m->id, 'player_id' => $this->user1->id, 'team' => 1]);
        }

        // user2: 10 wins, 10 losses -> 10 / max(1, 10) = 1.0
        for ($i = 0; $i < 10; $i++) {
            $m = GameMatch::create(['game_mode' => '1v1_PVP', 'concluded_at' => $thisWeek, 'winning_team_id' => 1]);
            MatchParticipant::create(['match_id' => $m->id, 'player_id' => $this->user2->id, 'team' => 1]);
        }
        for ($i = 0; $i < 10; $i++) {
            $m = GameMatch::create(['game_mode' => '1v1_PVP', 'concluded_at' => $thisWeek, 'winning_team_id' => 2]);
            MatchParticipant::create(['match_id' => $m->id, 'player_id' => $this->user2->id, 'team' => 1]);
        }

        $response = $this->actingAs($this->user1)
            ->getJson('/api/v1/leaderboard?mode=1v1_PVP');

        $response->assertStatus(200);
        $results = $response->json('data.results');

        $this->assertEquals('TopPlayer', $results[0]['account_name']);
        $this->assertEquals(10.0, $results[0]['score']);

        $this->assertEquals('MidPlayer', $results[1]['account_name']);
        $this->assertEquals(1.0, $results[1]['score']);
    }

    public function test_self_context_always_included()
    {
         $thisWeek = now('UTC')->startOfWeek(Carbon::SUNDAY)->addHour();

         // Populate top 15 players so player 16 is off page 1
         $users = [];
         for($i=0; $i<15; $i++) {
            $u = User::create(['account_name' => "Bot{$i}", 'email' => "bot{$i}@example.com", 'password_hash' => 'x']);
            $m = GameMatch::create(['game_mode' => '1v1_PVP', 'concluded_at' => $thisWeek, 'winning_team_id' => 1]);
            MatchParticipant::create(['match_id' => $m->id, 'player_id' => $u->id, 'team' => 1]);
            $users[] = $u;
         }

         // Current user (user1) has 0 matches
         $response = $this->actingAs($this->user1)
            ->getJson('/api/v1/leaderboard?mode=1v1_PVP&page=1');

         $response->assertStatus(200);
         // user1 should be in 'self' but not in 'results' (since 0 matches)
         $this->assertEquals('TopPlayer', $response->json('data.self.account_name'));
         $this->assertEquals('Unranked', $response->json('data.self.rank'));

         // user1 wins 1 match -> now ranked at bottom
         $m = GameMatch::create(['game_mode' => '1v1_PVP', 'concluded_at' => $thisWeek, 'winning_team_id' => 1]);
         MatchParticipant::create(['match_id' => $m->id, 'player_id' => $this->user1->id, 'team' => 1]);

         $response = $this->actingAs($this->user1)
            ->getJson('/api/v1/leaderboard?mode=1v1_PVP&page=1');

         $this->assertEquals(16, $response->json('data.self.rank'));
         $this->assertCount(10, $response->json('data.results'));
         // user1 is rank 16, so not on page 1 results
         $results = collect($response->json('data.results'));
         $this->assertFalse($results->contains('is_self', true));
    }
}
