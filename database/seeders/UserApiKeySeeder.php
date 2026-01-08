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

        $totalManualReviewKeys = 0;

        foreach ($users as $user) {
            // Create 1-3 API keys per user
            $apiKeyCount = rand(1, 3);
            $manualReviewCount = 0;

            for ($i = 0; $i < $apiKeyCount; $i++) {
                $needManualReview = fake()->boolean(30); // 30% chance

                $factory = UserApiKey::factory();
                if ($needManualReview) {
                    $factory = $factory->withManualReview();
                    $manualReviewCount++;
                    $totalManualReviewKeys++;
                }

                $factory->create(['user_id' => $user->id]);
            }

            $manualReviewInfo = $manualReviewCount > 0 ? " ({$manualReviewCount} with manual review)" : '';
            $this->command->info("✓ Created {$apiKeyCount} API key(s) for {$user->email}{$manualReviewInfo}");
        }

        $this->command->info("✓ Total {$totalManualReviewKeys} API key(s) with manual review enabled");

        $this->command->info('✓ All API keys created');
    }
}
