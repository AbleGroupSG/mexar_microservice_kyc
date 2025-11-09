<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating standard users...');

        for ($i = 1; $i <= 5; $i++) {
            User::factory()->create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => bcrypt('password'),
            ]);

            $this->command->info("✓ User {$i} created: user{$i}@example.com / password");
        }

        $this->command->info('✓ All standard users created');
    }
}
