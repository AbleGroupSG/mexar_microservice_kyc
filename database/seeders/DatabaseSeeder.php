<?php

namespace Database\Seeders;

use App\Models\ApiRequestLog;
use App\Models\KYCProfile;
use App\Models\User;
use App\Models\UserApiKey;
use Illuminate\Database\Seeder;

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
        $this->command->info('');

        // Call other seeders
        $this->call([
            UserSeeder::class,
            UserApiKeySeeder::class,
            KYCProfileSeeder::class,
            ApiRequestLogSeeder::class,
        ]);

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
