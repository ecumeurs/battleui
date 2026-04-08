<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponder;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\API\Auth\LoginRequest;
use App\Http\Requests\API\Auth\RegisterRequest;
use App\Http\Requests\API\Auth\UpdateAccountRequest;
use App\Http\Resources\UserResource;

/**
 * @spec-link [[api_auth_login]]
 * @spec-link [[api_auth_register]]
 * @spec-link [[rule_password_policy]]
 */
class AuthController extends Controller
{
    use ApiResponder;
    /**
     * @spec-link [[api_auth_login]]
     */
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $query = User::query();
        
        if (isset($validated['account_name'])) {
            $query->where('account_name', $validated['account_name']);
        } else {
            $query->where('email', $validated['email']);
        }

        $user = $query->first();

        if (! $user || ! Hash::check($validated['password'], $user->password_hash)) {
            return $this->error('Invalid credentials.', 401);
        }

        $token = $user->createToken('auth_token', expiresAt: now()->addMinutes(15))->plainTextToken;

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'Login successful.');
    }

    /**
     * @spec-link [[api_auth_register]]
     */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'account_name' => $validated['account_name'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'full_address' => $validated['full_address'],
            'birth_date' => $validated['birth_date'],
        ]);

        \App\Models\Character::generateInitialRoster($user->id);

        $token = $user->createToken('auth_token', expiresAt: now()->addMinutes(15))->plainTextToken;

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'Registration successful.', 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out.');
    }

    public function updateAccount(UpdateAccountRequest $request)
    {
        $user = $request->user();
        
        $validated = $request->validated();

        $user->update($validated);

        return $this->success(new UserResource($user), 'Account updated.');
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return $this->success(null, 'Account deleted.');
    }
}
