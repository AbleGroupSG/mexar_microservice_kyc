<?php

namespace Database\Seeders;

use App\Models\ApiRequestLog;
use App\Models\KYCProfile;
use Illuminate\Database\Seeder;

class ApiRequestLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating API request logs...');

        $profiles = KYCProfile::all();
        $logCount = 0;

        foreach ($profiles as $profile) {
            // Map provider name for logs (glair_ai -> glair)
            $logProvider = $profile->provider === 'glair_ai' ? 'glair' : $profile->provider;

            ApiRequestLog::factory()->create([
                'request_uuid' => $profile->id,
                'provider' => $logProvider,
                'payload' => json_encode($profile->profile_data),
            ]);

            $logCount++;
        }

        $this->command->info("✓ Created {$logCount} API request log(s)");
    }
}
