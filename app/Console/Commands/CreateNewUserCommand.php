<?php

namespace App\Console\Commands;

use App\Enums\UserTypeEnum;
use App\Models\User;
use App\Models\UserApiKey;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateNewUserCommand extends Command
{
    protected $signature = 'app:user:new
        {--name= : User name (required)}
        {--email= : User email for login (required)}
        {--password= : User password for login (required)}
        {--api-key-name= : API key name, e.g. "Production" (default: "Default")}
        {--api-key= : API key value (auto-generated if not provided)}
        {--webhook-url= : Webhook URL for receiving KYC results}
        {--signature-key= : Signature key for webhook verification}';

    protected $description = 'Create a new user with login credentials and API key';

    public function handle(): int
    {
        $name = $this->getNameInput();
        $email = $this->getEmailInput();
        $password = $this->getPasswordInput();
        $apiKeyName = $this->option('api-key-name') ?: 'Default';
        $apiKeyValue = $this->option('api-key') ?: Str::random(80);
        $webhookUrl = $this->option('webhook-url');
        $signatureKey = $this->option('signature-key');

        // Interactive mode: ask for webhook URL if not provided
        if (empty($webhookUrl) && $this->isInteractiveMode()) {
            $webhookUrl = $this->ask('Enter webhook URL for receiving KYC results (optional, press Enter to skip)');
        }

        // Create user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'user_type' => UserTypeEnum::USER,
        ]);

        // Create API key
        $apiKey = UserApiKey::create([
            'user_id' => $user->id,
            'name' => $apiKeyName,
            'api_key' => $apiKeyValue,
            'webhook_url' => $webhookUrl ?: null,
            'signature_key' => $signatureKey ?: null,
        ]);

        $this->displayResults($user, $apiKey, $apiKeyValue);

        return Command::SUCCESS;
    }

    private function getNameInput(): string
    {
        $name = $this->option('name');

        if (empty($name)) {
            $name = $this->ask('Please enter the user name');
        }

        if (empty($name)) {
            $this->error('User name is required.');
            exit(Command::FAILURE);
        }

        return $name;
    }

    private function getEmailInput(): string
    {
        $email = $this->option('email');

        if (empty($email)) {
            $email = $this->ask('Please enter the user email');
        }

        if (empty($email)) {
            $this->error('Email is required.');
            exit(Command::FAILURE);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email format.');
            exit(Command::FAILURE);
        }

        if (User::where('email', $email)->exists()) {
            $this->error("A user with email '{$email}' already exists.");
            exit(Command::FAILURE);
        }

        return $email;
    }

    private function getPasswordInput(): string
    {
        $password = $this->option('password');

        if (empty($password)) {
            $password = $this->secret('Please enter the password');

            if (empty($password)) {
                $this->error('Password is required.');
                exit(Command::FAILURE);
            }

            $passwordConfirmation = $this->secret('Please confirm the password');

            if ($password !== $passwordConfirmation) {
                $this->error('Passwords do not match.');
                exit(Command::FAILURE);
            }
        }

        return $password;
    }

    private function isInteractiveMode(): bool
    {
        return empty($this->option('name'))
            || empty($this->option('email'))
            || empty($this->option('password'));
    }

    private function displayResults(User $user, UserApiKey $apiKey, string $apiKeyValue): void
    {
        $this->newLine();
        $this->info('User created successfully!');
        $this->newLine();

        $this->table(
            ['Field', 'Value'],
            [
                ['User ID', $user->id],
                ['Name', $user->name],
                ['Email', $user->email],
                ['User Type', $user->user_type->value],
            ]
        );

        $this->newLine();
        $this->info('API Key created:');
        $this->newLine();

        $this->table(
            ['Field', 'Value'],
            [
                ['API Key Name', $apiKey->name],
                ['API Key', $apiKeyValue],
                ['Webhook URL', $apiKey->webhook_url ?: '(not set)'],
                ['Signature Key', $apiKey->signature_key ?: '(not set)'],
            ]
        );

        $this->newLine();
        $this->warn('⚠️  Please save the API key securely. It will not be shown again.');
    }
}
