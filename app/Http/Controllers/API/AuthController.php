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
use App\Http\Requests\API\Auth\ChangePasswordRequest;
use App\Http\Resources\UserResource;

/**
 * @spec-link [[api_auth_login]]
 * @spec-link [[api_auth_register]]
 * @spec-link [[rule_password_policy]]
 * @spec-link [[customer_user_account]]
 * @spec-link [[rule_gdpr_compliance]]
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

        $user = User::where('account_name', $validated['account_name'])->first();

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

    /**
     * @spec-link [[api_auth_logout]]
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out.');
    }

    /**
     * @spec-link [[customer_user_account]]
     */
    public function updateAccount(UpdateAccountRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->update($validated);

        return $this->success(new UserResource($user), 'Account updated.');
    }

    /**
     * @spec-link [[customer_user_account]]
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        if (!Hash::check($validated['current_password'], $user->password_hash)) {
            return $this->error('Current password does not match nuestro records.', 400);
        }

        $user->update([
            'password_hash' => Hash::make($validated['password']),
        ]);

        return $this->success(null, 'Password changed successfully.');
    }

    /**
     * @spec-link [[rule_gdpr_compliance]]
     */
    public function exportAccount(Request $request)
    {
        $user = $request->user()->load('characters');
        
        $data = [
            'account' => new UserResource($user),
            'characters' => \App\Http\Resources\CharacterResource::collection($user->characters),
            'meta' => [
                'export_date' => now()->toIso8601String(),
                'system' => 'Upsilon Battle UI',
            ]
        ];

        return response()->json($data, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="upsilon_identity_export.json"'
        ]);
    }

    /**
     * @spec-link [[rule_gdpr_compliance]]
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        
        // Anonymize before soft delete per GDPR rule
        $user->anonymize();
        
        $user->tokens()->delete();
        $user->delete();

        return $this->success(null, 'Account terminated and anonymized.');
    }
}
