<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * @spec-link [[api_auth_login]]
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'request_id' => $request->header('X-Request-ID', (string) str()->uuid()),
                'message' => 'Invalid credentials.',
                'success' => false,
                'data' => null,
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'request_id' => $request->header('X-Request-ID', (string) str()->uuid()),
            'message' => 'Login successful.',
            'success' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    /**
     * @spec-link [[api_auth_register]]
     */
    public function register(Request $request)
    {
        $request->validate([
            'account_name' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'account_name' => $request->account_name,
            'email' => $request->email,
            'password_hash' => Hash::make($request->password),
        ]);

        \App\Models\Character::generateInitialRoster($user->id);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'request_id' => $request->header('X-Request-ID', (string) str()->uuid()),
            'message' => 'Registration successful.',
            'success' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'request_id' => $request->header('X-Request-ID', (string) str()->uuid()),
            'message' => 'Logged out.',
            'success' => true,
            'data' => null,
        ]);
    }

    public function updateAccount(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'account_name' => 'string|max:255|unique:users,account_name,' . $user->id,
            'email' => 'string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return response()->json([
            'request_id' => $request->header('X-Request-ID', (string) str()->uuid()),
            'message' => 'Account updated.',
            'success' => true,
            'data' => $user,
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'request_id' => $request->header('X-Request-ID', (string) str()->uuid()),
            'message' => 'Account deleted.',
            'success' => true,
            'data' => null,
        ]);
    }
}
