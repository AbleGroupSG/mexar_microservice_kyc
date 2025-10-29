<?php

namespace Database\Seeders;

use App\Models\ApiRequestLog;
use App\Models\KYCProfile;
use App\Models\User;
use App\Models\UserApiKey;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding database for local development...');

        // Create Admin User
        $this->command->info('Creating admin user...');
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create admin API keys
        UserApiKey::factory()->count(2)->create([
            'user_id' => $admin->id,
        ]);

        $this->command->info('✓ Admin user created: admin@example.com / password');

        // Create Standard Users with API Keys and KYC Profiles
        $this->command->info('Creating standard users...');

        for ($i = 1; $i <= 5; $i++) {
            $user = User::factory()->create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => bcrypt('password'),
            ]);

            // Create 1-3 API keys per user
            $apiKeyCount = rand(1, 3);
            $apiKeys = UserApiKey::factory()->count($apiKeyCount)->create([
                'user_id' => $user->id,
            ]);

            // Create 3-8 KYC profiles per user with various statuses
            $profileCount = rand(3, 8);
            for ($j = 0; $j < $profileCount; $j++) {
                $uuid = (string) Str::uuid();
                $apiKey = $apiKeys->random();

                // Randomize provider
                $provider = fake()->randomElement(['regtank', 'glair_ai', 'test']);

                // Create KYC Profile
                $status = fake()->randomElement(['approved', 'rejected', 'pending', 'pending']);
                $profile = KYCProfile::factory()
                    ->$status()
                    ->create([
                        'id' => $uuid,
                        'user_id' => $user->id,
                        'user_api_key_id' => $apiKey->id,
                        'provider' => $provider,
                    ]);

                // Create corresponding API Request Log
                $logProvider = $provider === 'glair_ai' ? 'glair' : $provider;
                ApiRequestLog::factory()->create([
                    'request_uuid' => $uuid,
                    'provider' => $logProvider,
                    'payload' => json_encode($profile->profile_data),
                ]);
            }

            $this->command->info("✓ User {$i} created: user{$i}@example.com / password");
        }

        $this->command->info('✓ All users and profiles created');

        // Summary
        $this->command->info('');
        $this->command->info('=================================');
        $this->command->info('🎉 Database seeding completed!');
        $this->command->info('=================================');
        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('• Users: ' . User::count());
        $this->command->info('  - Admin: 1 (admin@example.com)');
        $this->command->info('  - Standard: 5 (user1-5@example.com)');
        $this->command->info('• API Keys: ' . UserApiKey::count());
        $this->command->info('• KYC Profiles: ' . KYCProfile::count());
        $this->command->info('  - Approved: ' . KYCProfile::where('status', 'APPROVED')->count());
        $this->command->info('  - Rejected: ' . KYCProfile::where('status', 'REJECTED')->count());
        $this->command->info('  - Pending: ' . KYCProfile::where('status', 'PENDING')->count());
        $this->command->info('• API Request Logs: ' . ApiRequestLog::count());
        $this->command->info('');
        $this->command->info('🔑 Login credentials:');
        $this->command->info('   Admin: admin@example.com / password');
        $this->command->info('   Users: user1@example.com - user5@example.com / password');
        $this->command->info('');
    }
}
