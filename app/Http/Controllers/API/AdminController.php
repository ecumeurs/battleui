<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponder;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\API\Auth\LoginRequest;

/**
 * @spec-link [[uc_admin_login]]
 * @spec-link [[uc_admin_user_management]]
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
     * List all users for auditing
     */
    public function users()
    {
        $users = User::withTrashed()->get();
        return $this->success(UserResource::collection($users), 'User registry retrieved.');
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
