<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateNewUserCommand extends Command
{
    protected $signature = 'create.new.user {--name=} {--apiKey=}';


    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $name = $this->option('name');
        $apiKey = $this->option('apiKey');
        if (empty($name)) {
            $name = $this->ask('Please enter the name of the user');
        }
        if (empty($apiKey)) {
            $apiKey = Str::random(80);
        }

        User::query()->create([
            'name' => $name,
            'api_key' => $apiKey,
        ]);

        $this->info("User created successfully with name: {$name} and API key: {$apiKey}");
    }
}
