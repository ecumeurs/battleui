<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\MatchMakingController;
use App\Http\Controllers\API\GameController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\WebhookController;

Route::post('/webhook/upsilon', [WebhookController::class, 'handle']);

/**
 * @spec-link [[api_laravel_gateway]]
 */
Route::prefix("v1")->group(function () {

    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/auth/update', [AuthController::class, 'updateAccount'])->middleware('auth:sanctum');
    Route::delete('/auth/delete', [AuthController::class, 'deleteAccount'])->middleware('auth:sanctum');

    Route::middleware('auth:sanctum')->group(function() {
        Route::get('/profile/{id}', [ProfileController::class, 'getProfile']);
        Route::post('/profile/{id}', [ProfileController::class, 'updateProfile']);
        Route::get('/profile/{id}/characters', [ProfileController::class, 'getCharacters']);
        Route::get('/profile/{id}/character/{characterId}', [ProfileController::class, 'getCharacter']);
        
        // Character actions
        Route::post('/profile/{id}/character/{characterId}/reroll', [ProfileController::class, 'rerollCharacter']);
        Route::post('/profile/{id}/character/{characterId}/upgrade', [ProfileController::class, 'updateCharacter']);
        Route::delete('/profile/{id}/character/{characterId}', [ProfileController::class, 'deleteCharacter']);

        // Matchmaking
        // @spec-link [[api_matchmaking]]
        Route::get('/matchmaking/status', [MatchMakingController::class, 'status']);
        Route::post('/matchmaking/join', [MatchMakingController::class, 'joinMatch']);
        Route::post('/matchmaking/leave', [MatchMakingController::class, 'leaveMatch']);

        // Game proxy
        Route::get('/game/{id}', [GameController::class, 'state']);
        Route::post('/game/{id}/action', [GameController::class, 'action']);
    });
});