<?php

namespace Tests\Feature\API;

use App\Models\User;
use App\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @spec-link [[rule_gdpr_compliance]]
 */
class GdprTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @spec-link [[rule_gdpr_compliance]]
     */
    public function test_user_account_soft_delete_and_anonymization()
    {
        $user = User::create([
            'account_name' => 'GdprPlayer',
            'email' => 'gdpr@example.com',
            'password_hash' => Hash::make('password123'),
            'full_address' => 'Secret Street 123',
            'birth_date' => '1990-01-01',
        ]);

        // Generate characters
        Character::generateInitialRoster($user->id);
        $charCount = $user->characters()->count();
        $this->assertEquals(3, $charCount);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->deleteJson('/api/v1/auth/delete', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Account terminated and anonymized.',
            ]);

        // Verify soft delete
        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);

        // Verify anonymization
        $deletedUser = User::withTrashed()->find($user->id);
        $this->assertEquals('ANONYMIZED', $deletedUser->full_address);
        $this->assertEquals('1900-01-01', $deletedUser->birth_date->format('Y-m-d'));

        // Verify characters still exist (no FK violation)
        $this->assertEquals(3, Character::where('player_id', $user->id)->count());
    }

    /**
     * @spec-link [[rule_gdpr_compliance]]
     */
    public function test_user_hard_delete_cascades_to_characters()
    {
        $user = User::create([
            'account_name' => 'HardDeletePlayer',
            'email' => 'hard@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        Character::generateInitialRoster($user->id);
        $this->assertEquals(3, Character::where('player_id', $user->id)->count());

        // Force delete the user
        $user->forceDelete();

        // Verify user is gone
        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        // Verify characters are gone (cascading delete)
        $this->assertDatabaseMissing('characters', ['player_id' => $user->id]);
    }
}
