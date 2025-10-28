<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AppSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup the application for fresh installation';

    /**
     * Required environment variables for the application.
     *
     * @var array
     */
    protected $requiredEnvVars = [
        'COMPANY_SPECIFIC_REGTANK_SERVICE_URL' => 'Company Specific RegTank Service URL',
        'REGTANK_CRM_SERVER_URL' => 'RegTank CRM Server URL',
        'CLIENT_ID_TEMPLATE' => 'Client ID Template',
        'CLIENT_SECRET_TEMPLATE' => 'Client Secret Template',
        'REGTANK_ASIGNEE' => 'RegTank Assignee',
        'GLAIR_OCR_BASE_URL' => 'Glair OCR Base URL',
        'GLAIR_API_KEY' => 'Glair API Key',
        'GLAIR_USERNAME' => 'Glair Username',
        'GLAIR_PASSWORD' => 'Glair Password',
        'MEXAR_URL' => 'Mexar URL',
        'MEXAR_SINGATURE_SECRET' => 'Mexar Signature Secret',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting application setup...');
        $this->newLine();

        // Check and create .env file
        if (!$this->setupEnvFile()) {
            return 1;
        }

        // Collect environment variables
        $this->collectEnvVariables();

        // Generate application key
        $this->generateAppKey();

        // Run migrations
        $this->runMigrations();

        // Setup webhook
        $this->setupWebhook();

        $this->newLine();
        $this->info('Application setup completed successfully!');

        return 0;
    }

    /**
     * Check if .env exists, if not copy from .env.example
     *
     * @return bool
     */
    protected function setupEnvFile()
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        if (File::exists($envPath)) {
            if (!$this->confirm('.env file already exists. Do you want to overwrite it?', false)) {
                $this->warn('Using existing .env file. Skipping to environment variable collection.');
                return true;
            }
        }

        if (!File::exists($envExamplePath)) {
            $this->error('.env.example file not found!');
            return false;
        }

        File::copy($envExamplePath, $envPath);
        $this->info('.env file created from .env.example');

        return true;
    }

    /**
     * Collect environment variables from user input
     */
    protected function collectEnvVariables()
    {
        $this->newLine();
        $this->info('Please provide the following environment variables:');
        $this->newLine();

        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        foreach ($this->requiredEnvVars as $key => $description) {
            // Check if variable already has a value
            $currentValue = $this->getEnvValue($envContent, $key);

            $defaultText = $currentValue ? " (current: {$currentValue})" : '';
            $value = $this->ask("{$description} ({$key}){$defaultText}", $currentValue);

            if ($value) {
                $envContent = $this->updateEnvVariable($envContent, $key, $value);
            }
        }

        File::put($envPath, $envContent);
        $this->info('Environment variables updated successfully!');
    }

    /**
     * Get current value of an environment variable from .env content
     *
     * @param string $envContent
     * @param string $key
     * @return string|null
     */
    protected function getEnvValue($envContent, $key)
    {
        if (preg_match("/^{$key}=(.*)$/m", $envContent, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Update or add an environment variable in .env content
     *
     * @param string $envContent
     * @param string $key
     * @param string $value
     * @return string
     */
    protected function updateEnvVariable($envContent, $key, $value)
    {
        // Escape value if it contains spaces or special characters
        if (preg_match('/\s/', $value) || preg_match('/[#"\']/', $value)) {
            $value = '"' . addslashes($value) . '"';
        }

        // Check if key exists
        if (preg_match("/^{$key}=/m", $envContent)) {
            // Update existing key
            $envContent = preg_replace(
                "/^{$key}=.*$/m",
                "{$key}={$value}",
                $envContent
            );
        } else {
            // Add new key at the end
            $envContent .= "\n{$key}={$value}";
        }

        return $envContent;
    }

    /**
     * Generate application key
     */
    protected function generateAppKey()
    {
        $this->newLine();
        if ($this->confirm('Do you want to generate a new application key?', true)) {
            $this->call('key:generate');
        }
    }

    /**
     * Run database migrations
     */
    protected function runMigrations()
    {
        $this->newLine();
        if ($this->confirm('Do you want to run database migrations?', true)) {
            $this->call('migrate');
        }
    }

    /**
     * Setup webhook
     */
    protected function setupWebhook()
    {
        $this->newLine();
        if ($this->confirm('Do you want to register the webhook?', true)) {
            $this->call('mexar:enable-webhook');
        }
    }
}
