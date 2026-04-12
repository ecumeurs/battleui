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
    }
}
