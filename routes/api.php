<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\MatchMakingController;
use App\Http\Controllers\API\GameController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\WebhookController;

Route::post('/webhook/upsilon', [WebhookController::class, 'handle']);

Route::group(["api/v1"], function () {

    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/update', [AuthController::class, 'updateAccount']);
    Route::delete('/auth/delete', [AuthController::class, 'deleteAccount']);

    Route::get('/profile/{id}', [ProfileController::class, 'getProfile']);
    Route::post('/profile/{id}', [ProfileController::class, 'updateProfile']);
    Route::get('/profile/{id}/character/{characterId}', [ProfileController::class, 'getCharacter']);
    Route::get('/profile/{id}/characters', [ProfileController::class, 'getCharacters']);
    Route::post('/profile/{id}/character/{characterId}', [ProfileController::class, 'rerollCharacter']);
    Route::post('/profile/{id}/character/{characterId}', [ProfileController::class, 'updateCharacter']);
    Route::delete('/profile/{id}/character/{characterId}', [ProfileController::class, 'deleteCharacter']);

    Route::post('/matchmaking/{id}', [MatchMakingController::class, 'joinMatch']);
    Route::post('/matchmaking/{id}', [MatchMakingController::class, 'leaveMatch']);

    Route::get('/game/{id}', [GameController::class, 'state']);
    Route::post('/game/{id}', [GameController::class, 'action']);
});