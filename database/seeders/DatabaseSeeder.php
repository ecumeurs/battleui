<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\QuizDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /** @spec-link [[upsilonbattle:entity_shop_item]] */
        $this->call(ShopItemsSeeder::class);

        /** @spec-link [[entity_skill_template]] */
        $this->call(SkillTemplatesSeeder::class);


        /** @spec-link [[infra_seed_admin]] */
        $adminPassword = env('ADMIN_INITIAL_PASSWORD');

        if (!$adminPassword) {
            \Illuminate\Support\Facades\Log::warning('ADMIN_INITIAL_PASSWORD not set. Admin seeding skipped.');
            return;
        }

        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'account_name' => 'admin',
                'password_hash' => \Illuminate\Support\Facades\Hash::make($adminPassword),
                'role' => 'Admin',
                'full_address' => 'SYS_ADMIN_LOCAL',
                'birth_date' => '1970-01-01',
            ]
        );

        // Add a dummy user to prevent "last admin" anonymization failure in E2E tests
        User::updateOrCreate(
            ['email' => 'dummy@example.com'],
            [
                'account_name' => 'dummy_user',
                'password_hash' => \Illuminate\Support\Facades\Hash::make('DummyPassword123!'),
                'role' => 'Player',
                'full_address' => 'Dummy St',
                'birth_date' => '1990-01-01',
            ]
        );

        // Add a second admin to allow "anonymize" testing on one of them
        User::updateOrCreate(
            ['email' => 'admin2@admin.com'],
            [
                'account_name' => 'admin2',
                'password_hash' => \Illuminate\Support\Facades\Hash::make('AdminPassword123!'),
                'role' => 'Admin',
                'full_address' => 'SYS_ADMIN_LOCAL_2',
                'birth_date' => '1970-01-01',
            ]
        );
    }


}
