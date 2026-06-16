<?php

namespace Tests\Feature\API;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @spec-link [[uc_admin_user_management]]
 * @test-link [[uc_admin_user_management]]
 *
 * ISS-093 — Admin destructive-action self-protection
 *
 * Verifies that an authenticated admin cannot anonymize or delete their own
 * account via the API, while still being able to act on other users.
 */
class AdminSelfProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'account_name' => 'AdminUser',
            'email'         => 'admin@example.com',
            'password_hash' => Hash::make('AdminPass1!'),
            'role'          => 'Admin',
        ]);

        $this->otherUser = User::create([
            'account_name' => 'OtherPlayer',
            'email'         => 'other@example.com',
            'password_hash' => Hash::make('PlayerPass1!'),
        ]);
    }

    // -----------------------------------------------------------------------
    // (a) Self-anonymize — must be blocked with 403
    // -----------------------------------------------------------------------

    /**
     * @spec-link [[uc_admin_user_management]]
     * @test-link [[uc_admin_user_management]]
     */
    public function test_admin_cannot_anonymize_itself(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->admin->account_name}/anonymize");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot perform destructive actions on your own administrative account.',
            ]);

        // Verify the account was NOT mutated
        $this->assertDatabaseHas('users', [
            'id'    => $this->admin->id,
            'email' => 'admin@example.com',
        ]);
    }

    // -----------------------------------------------------------------------
    // (b) Self-delete — must be blocked with 403
    // -----------------------------------------------------------------------

    /**
     * @spec-link [[uc_admin_user_management]]
     * @test-link [[uc_admin_user_management]]
     */
    public function test_admin_cannot_delete_itself(): void
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/users/{$this->admin->account_name}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot perform destructive actions on your own administrative account.',
            ]);

        // Verify the account was NOT soft-deleted
        $this->assertDatabaseHas('users', [
            'id'         => $this->admin->id,
            'deleted_at' => null,
        ]);
    }

    // -----------------------------------------------------------------------
    // (c) Acting on a DIFFERENT user — must succeed
    // -----------------------------------------------------------------------

    /**
     * @spec-link [[uc_admin_user_management]]
     * @test-link [[uc_admin_user_management]]
     */
    public function test_admin_can_anonymize_another_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->otherUser->account_name}/anonymize");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User data anonymized.',
            ]);

        $this->assertDatabaseHas('users', [
            'id'           => $this->otherUser->id,
            'full_address' => 'ANONYMIZED',
        ]);
    }

    /**
     * @spec-link [[uc_admin_user_management]]
     * @test-link [[uc_admin_user_management]]
     */
    public function test_admin_can_delete_another_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/users/{$this->otherUser->account_name}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User account deactivated.',
            ]);

        $this->assertSoftDeleted('users', ['id' => $this->otherUser->id]);
    }
}
