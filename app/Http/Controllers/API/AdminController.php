<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\GameMatch;
use App\Http\Resources\UserResource;
use App\Http\Resources\GameMatchResource;
use App\Traits\ApiResponder;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\API\Auth\LoginRequest;

/**
 * @spec-link [[uc_admin_login]]
 * @spec-link [[uc_admin_user_management]]
 * @spec-link [[uc_admin_history_management]]
 * @api-tag Admin
 */
class AdminController extends Controller
{
    use ApiResponder;

    /**
     * @spec-link [[api_auth_login]]
     * @api-output [[AuthResponse]]
     * 
     * Specialized Admin Login for API clients/CLI
     */
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('account_name', $validated['account_name'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password_hash)) {
            return $this->error('Invalid credentials.', 401);
        }

        if (! $user->isAdmin()) {
            return $this->error('Access denied. Administrative privileges required.', 403);
        }

        $token = $user->createToken('admin_token', expiresAt: now()->addHours(2))->plainTextToken;

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'Admin login successful.');
    }

    /**
     * @spec-link [[uc_admin_user_management]]
     * @api-output [[UserListResponse]]
     * 
     * List all users for auditing with manual pagination and search (ISS-053)
     */
    public function users(Request $request)
    {
        $query = User::withTrashed();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('account_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $cursor = $request->input('cursor');
        if ($cursor) {
            $query->where('updated_at', '<', $cursor);
        }

        $users = $query->orderBy('updated_at', 'desc')
                       ->limit(51)
                       ->get();

        $hasMore = $users->count() > 50;
        if ($hasMore) {
            $users->pop();
        }

        return $this->success([
            'items' => UserResource::collection($users),
            'next_cursor' => $hasMore ? $users->last()->updated_at->toISOString() : null,
            'has_more' => $hasMore
        ], 'User registry retrieved.');
    }

    /**
     * @spec-link [[uc_admin_history_management]]
     * 
     * List match history with manual pagination and search (ISS-051, ISS-053)
     */
    public function history(Request $request)
    {
        $query = GameMatch::with('participants.player');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('participants.player', function($pq) use ($search) {
                      $pq->where('account_name', 'like', "%{$search}%");
                  });
            });
        }

        $cursor = $request->input('cursor');
        if ($cursor) {
            $query->where('updated_at', '<', $cursor);
        }

        $matches = $query->orderBy('updated_at', 'desc')
                         ->limit(51)
                         ->get();

        $hasMore = $matches->count() > 50;
        if ($hasMore) {
            $matches->pop();
        }

        return $this->success([
            'items' => GameMatchResource::collection($matches),
            'next_cursor' => $hasMore ? $matches->last()->updated_at->toISOString() : null,
            'has_more' => $hasMore
        ], 'Match history retrieved.');
    }

    /**
     * @spec-link [[uc_admin_history_management]]
     * 
     * Purge history older than 90 days (ISS-051)
     */
    public function purge()
    {
        $count = GameMatch::where('concluded_at', '<', now()->subDays(90))->delete();

        return $this->success(['purged_count' => $count], "Purge complete: {$count} records removed.");
    }

    /**
     * Anonymize a user (GDPR)
     */
    public function anonymize(User $user)
    {
        if ($user->isAdmin() && User::where('role', 'Admin')->count() <= 1) {
            return $this->error('Cannot anonymize the last remaining administrator.', 400);
        }

        $user->anonymize();
        return $this->success(new UserResource($user), 'User data anonymized.');
    }

    /**
     * Soft delete a user
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return $this->error('Cannot delete your own active administrative session.', 400);
        }

        $user->delete();
        return $this->success(null, 'User account deactivated.');
    }
}
