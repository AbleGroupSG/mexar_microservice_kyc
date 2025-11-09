<?php

namespace Database\Seeders;

use App\Models\KYCProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KYCProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating KYC profiles...');

        // Get all non-admin users with their API keys
        $users = User::where('user_type', '!=', 'admin')
            ->orWhereNull('user_type')
            ->with('apiKeys')
            ->get();

        $totalProfiles = 0;

        foreach ($users as $user) {
            if ($user->apiKeys->isEmpty()) {
                continue;
            }

            // Create 3-8 KYC profiles per user
            $profileCount = rand(3, 8);

            for ($j = 0; $j < $profileCount; $j++) {
                $uuid = (string) Str::uuid();
                $apiKey = $user->apiKeys->random();

                // Randomize provider
                $provider = fake()->randomElement(['regtank', 'glair_ai', 'test']);

                // Create KYC Profile with randomized status
                $status = fake()->randomElement(['approved', 'rejected', 'pending', 'pending']);
                KYCProfile::factory()
                    ->$status()
                    ->create([
                        'id' => $uuid,
                        'user_id' => $user->id,
                        'user_api_key_id' => $apiKey->id,
                        'provider' => $provider,
                    ]);

                $totalProfiles++;
            }

            $this->command->info("✓ Created {$profileCount} KYC profile(s) for {$user->email}");
        }

        $this->command->info("✓ Total {$totalProfiles} KYC profiles created");
    }
}
