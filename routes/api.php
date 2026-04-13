<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\MatchMakingController;
use App\Http\Controllers\API\GameController;
use App\Http\Controllers\API\AdminController as ApiAdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\WebhookController;
use App\Http\Controllers\API\LeaderboardController;

Route::post('/webhook/upsilon', [WebhookController::class, 'handle']);

// Test route for error handling verification - only active in testing
if (app()->environment('testing')) {
    Route::get('/v1/test-error', function () {
        throw new \Exception("Test Exception for verification");
    });
}

/**
 * @spec-link [[api_laravel_gateway]]
 */
Route::prefix("v1")->group(function () {

    /** @spec-link [[api_help_endpoint]] */
    Route::get('/help', [\App\Http\Controllers\API\HelpController::class, 'index']);

    /** @spec-link [[api_auth_user]] */
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/admin/login', [ApiAdminController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function() {
        // Account Identity (Auth) - @spec-link [[api_auth_user]]
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/update', [AuthController::class, 'updateAccount']);
        Route::post('/auth/password', [AuthController::class, 'changePassword']);
        Route::get('/auth/export', [AuthController::class, 'exportAccount']);
        Route::delete('/auth/delete', [AuthController::class, 'deleteAccount']);

        // Player Profile & Meta-game (Profile)
        // @spec-link [[api_profile_character]]
        Route::get('/profile', [ProfileController::class, 'getProfile']);
        Route::get('/profile/characters', [ProfileController::class, 'getCharacters']);
        Route::get('/profile/character/{characterId}', [ProfileController::class, 'getCharacter']);
        
        // Character actions
        Route::post('/profile/character/{characterId}/reroll', [ProfileController::class, 'rerollCharacter']);
        Route::post('/profile/character/{characterId}/upgrade', [ProfileController::class, 'updateCharacter']);
        Route::post('/profile/character/{characterId}/rename', [ProfileController::class, 'renameCharacter']);
        Route::delete('/profile/character/{characterId}', [ProfileController::class, 'deleteCharacter']);

        // Matchmaking
        // @spec-link [[api_matchmaking]]
        Route::get('/matchmaking/status', [MatchMakingController::class, 'status']);
        Route::post('/matchmaking/join', [MatchMakingController::class, 'joinMatch']);
        Route::delete('/matchmaking/leave', [MatchMakingController::class, 'leaveMatch']);

        // Stats
        // @spec-link [[ui_dashboard_match_statistics]]
        Route::get('/match/stats/waiting', [MatchMakingController::class, 'getWaitingStats']);
        Route::get('/match/stats/active', [MatchMakingController::class, 'getActiveStats']);

        // Leaderboard
        // @spec-link [[api_leaderboard]]
        Route::get('/leaderboard', [LeaderboardController::class, 'index']);

        // Game proxy
        Route::get('/game/{id}', [GameController::class, 'state']);
        Route::post('/game/{id}/action', [GameController::class, 'action']);
        Route::post('/game/{id}/forfeit', [GameController::class, 'forfeit']);

        // Administrative Layer
        Route::middleware('admin')->prefix('admin')->group(function() {
            Route::get('/users', [ApiAdminController::class, 'users']);
            Route::post('/users/{user:account_name}/anonymize', [ApiAdminController::class, 'anonymize']);
            Route::delete('/users/{user:account_name}', [ApiAdminController::class, 'destroy']);
        });
    });
});
