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

        // Get admin user for reviewed profiles
        $adminUser = User::where('user_type', 'admin')->first();
        if (! $adminUser) {
            $this->command->warn('No admin user found. Reviewed profiles will not have reviewer.');
        }

        // Get all non-admin users with their API keys
        $users = User::where('user_type', '!=', 'admin')
            ->orWhereNull('user_type')
            ->with('apiKeys')
            ->get();

        $totalProfiles = 0;
        $awaitingReviewCount = 0;
        $reviewedCount = 0;

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

                if ($apiKey->need_manual_review) {
                    // Manual review workflow
                    $isAwaitingReview = fake()->boolean(40); // 40% awaiting review

                    if ($isAwaitingReview) {
                        // Awaiting review - intermediate status
                        $status = fake()->randomElement(['providerApproved', 'providerRejected', 'providerError']);
                        KYCProfile::factory()
                            ->$status()
                            ->create([
                                'id' => $uuid,
                                'user_id' => $user->id,
                                'user_api_key_id' => $apiKey->id,
                                'provider' => $provider,
                            ]);
                        $awaitingReviewCount++;
                    } else {
                        // Already reviewed - final status with review data
                        $status = fake()->randomElement(['approved', 'rejected']);
                        $factory = KYCProfile::factory()->$status();

                        if ($adminUser) {
                            $factory = $factory->reviewed($adminUser);
                        }

                        $factory->create([
                            'id' => $uuid,
                            'user_id' => $user->id,
                            'user_api_key_id' => $apiKey->id,
                            'provider' => $provider,
                        ]);
                        $reviewedCount++;
                    }
                } else {
                    // Standard workflow - final statuses only
                    $status = fake()->randomElement(['approved', 'rejected', 'pending', 'pending']);
                    KYCProfile::factory()
                        ->$status()
                        ->create([
                            'id' => $uuid,
                            'user_id' => $user->id,
                            'user_api_key_id' => $apiKey->id,
                            'provider' => $provider,
                        ]);
                }

                $totalProfiles++;
            }

            $this->command->info("✓ Created {$profileCount} KYC profile(s) for {$user->email}");
        }

        $this->command->info("✓ Total {$totalProfiles} KYC profiles created");
        $this->command->info("  - {$awaitingReviewCount} profile(s) awaiting manual review");
        $this->command->info("  - {$reviewedCount} profile(s) already reviewed");
    }
}
