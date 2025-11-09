<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserApiKey;
use Illuminate\Database\Seeder;

class UserApiKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating API keys for users...');

        // Get all non-admin users
        $users = User::where('user_type', '!=', 'admin')
            ->orWhereNull('user_type')
            ->get();

        foreach ($users as $user) {
            // Create 1-3 API keys per user
            $apiKeyCount = rand(1, 3);
            UserApiKey::factory()->count($apiKeyCount)->create([
                'user_id' => $user->id,
            ]);

            $this->command->info("✓ Created {$apiKeyCount} API key(s) for {$user->email}");
        }

        $this->command->info('✓ All API keys created');
    }
}
