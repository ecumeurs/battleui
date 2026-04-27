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
use App\Http\Controllers\API\ShopController;
use App\Http\Controllers\API\InventoryController;
use App\Http\Controllers\API\EquipmentController;
use App\Http\Controllers\API\SkillTemplateController;
use App\Http\Controllers\API\CharacterSkillController;

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
        Route::get('/profile/credits', [ProfileController::class, 'getCredits']);
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

        // Shop (ISS-074)
        // @spec-link [[upsilonapi:api_shop_browse]]
        // @spec-link [[upsilonapi:api_shop_purchase]]
        Route::get('/shop/items', [ShopController::class, 'index']);
        Route::post('/shop/purchase', [ShopController::class, 'purchase']);

        // Inventory & Equipment (ISS-074)
        // @spec-link [[upsilonapi:api_inventory_list]]
        // @spec-link [[upsilonapi:api_equipment_management]]
        Route::get('/profile/inventory', [InventoryController::class, 'index']);
        Route::get('/profile/character/{characterId}/equipment', [EquipmentController::class, 'show']);
        Route::post('/profile/character/{characterId}/equip', [EquipmentController::class, 'equip']);
        Route::delete('/profile/character/{characterId}/unequip/{slot}', [EquipmentController::class, 'unequip']);

        // Skill Templates — browsable catalog (ISS-086)
        // @spec-link [[api_skill_template_browse]]
        Route::get('/skills/templates', [SkillTemplateController::class, 'index']);
        Route::get('/skills/templates/{id}', [SkillTemplateController::class, 'show']);

        // Character Skill Inventory — slot-based loadout (ISS-073)
        // @spec-link [[api_character_skill_inventory]]
        // @spec-link [[rule_character_skill_slots]]
        Route::get('/profile/character/{characterId}/skills', [CharacterSkillController::class, 'index']);
        Route::post('/profile/character/{characterId}/skills/roll', [CharacterSkillController::class, 'roll']);
        Route::post('/profile/character/{characterId}/skills/{skillId}/equip', [CharacterSkillController::class, 'equip']);
        Route::delete('/profile/character/{characterId}/skills/{skillId}/unequip', [CharacterSkillController::class, 'unequip']);

        // Administrative Layer
        Route::middleware('admin')->prefix('admin')->group(function() {
            Route::get('/users', [ApiAdminController::class, 'users']);
            Route::post('/users/{user:account_name}/anonymize', [ApiAdminController::class, 'anonymize'])->withTrashed();
            Route::delete('/users/{user:account_name}', [ApiAdminController::class, 'destroy'])->withTrashed();

            Route::get('/history', [ApiAdminController::class, 'history']);
            Route::post('/history/purge', [ApiAdminController::class, 'purge']);
        });
    });
});
