<?php
 
 namespace Database\Seeders;
 
 use App\Models\User;
 use App\Models\Character;
 use Illuminate\Database\Seeder;
 use Illuminate\Support\Facades\Hash;
 
 class TestAccountSeeder extends Seeder
 {
     /**
      * Run the database seeds.
      * @spec-link [[infra_seed_test_account]]
      */
     public function run(): void
     {
         $user = User::updateOrCreate(
             ['account_name' => 'testuser'],
             [
                 'email' => 'test@example.com',
                 'password_hash' => Hash::make('TestUserPassword123!'),
                 'role' => 'Player',
                 'full_address' => 'Test Street 1',
                 'birth_date' => '1990-01-01',
                 'credits' => 1000,
                 'ws_channel_key' => '00000000-0000-0000-0000-000000000001',
             ]
         );
 
         // Ensure the user has characters
         if ($user->characters()->count() === 0) {
             Character::generateInitialRoster($user->id);
         }
     }
 }
