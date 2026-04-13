<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @spec-link [[api_leaderboard]]
 * @spec-link [[rule_leaderboard_score_calculation]]
 * @spec-link [[rule_leaderboard_cycle]]
 */
class LeaderboardController extends Controller
{
    /**
     * @spec-link [[api_leaderboard]]
     * @api-output [[LeaderboardResponse]]
     * 
     * Display a listing of the rankings.
     */
    public function index(Request $request)
    {
        $request->validate([
            'mode' => 'required|string|in:1v1_PVP,2v2_PVP,1v1_PVE,2v2_PVE',
            'page' => 'integer|min:1',
            'search' => 'nullable|string'
        ]);

        $mode = $request->mode;
        $search = trim($request->search);

        // Start of current week (Sunday 00:01 UTC)
        $now = Carbon::now('UTC');
        // If today is Sunday, startOfWeek(0) returns today. If Monday, returns yesterday (Sunday).
        $weekStart = $now->copy()->startOfWeek(Carbon::SUNDAY)->startOfDay()->addMinute();

        $query = DB::table('users')
            ->join('match_participants', 'users.id', '=', 'match_participants.player_id')
            ->join('game_matches', 'match_participants.match_id', '=', 'game_matches.id')
            ->where('game_matches.game_mode', $mode)
            ->where('game_matches.concluded_at', '>=', $weekStart->toDateTimeString())
            ->whereNotNull('game_matches.winning_team_id')
            ->select(
                'users.id',
                'users.account_name',
                DB::raw('COUNT(CASE WHEN game_matches.winning_team_id = match_participants.team THEN 1 END) as wins'),
                DB::raw('COUNT(CASE WHEN game_matches.winning_team_id != match_participants.team AND game_matches.winning_team_id > 0 THEN 1 END) as losses')
            )
            ->groupBy('users.id', 'users.account_name');

        if ($search) {
            $query->where('users.account_name', 'LIKE', "%{$search}%");
        }

        $results = $query->get()->map(function ($row) {
            $row->wins = (int) $row->wins;
            $row->losses = (int) $row->losses;
            $row->score = $row->wins / max(1, $row->losses);
            return $row;
        });

        // Filter out those with 0 matches globally (though the join and concluded_at should handle it)
        $results = $results->filter(function($row) {
            return ($row->wins + $row->losses) > 0;
        });

        $sorted = $results->sortByDesc(function ($row) {
            // Sort by Wins first, then Score
            return sprintf('%010d.%010d', $row->wins, (int)($row->score * 1000000));
        })->values();

        $currentUserId = auth()->id();

        $ranked = $sorted->map(function ($row, $index) use ($currentUserId) {
            $row->rank = $index + 1;
            $row->is_self = $row->id === $currentUserId;
            unset($row->id);
            return $row;
        });

        $page = (int) ($request->page ?? 1);
        $perPage = 10;

        $paginated = $ranked->slice(($page - 1) * $perPage, $perPage)->values();

        $self = null;
        if (auth()->check()) {
            $currentUser = auth()->user();
            $selfData = $ranked->firstWhere('is_self', true);
            if ($selfData) {
                $self = $selfData;
            } else {
                $self = [
                    'account_name' => $currentUser->account_name,
                    'rank' => 'Unranked',
                    'wins' => 0,
                    'losses' => 0,
                    'score' => 0,
                    'is_self' => true
                ];
            }
        }

        return response()->json([
            'request_id' => $request->header('X-Request-ID') ?? (string) str()->uuid(),
            'message' => $ranked->isEmpty() ? 'AREA SCAVENGED: NO SIGNS OF LIFE' : 'Leaderboard data retrieved',
            'success' => true,
            'data' => [
                'results' => $paginated,
                'self' => $self,
                'meta' => [
                    'current_page' => $page,
                    'last_page' => (int) ceil($results->count() / $perPage),
                    'total' => $results->count(),
                    'per_page' => $perPage
                ]
            ]
        ]);
    }
}
