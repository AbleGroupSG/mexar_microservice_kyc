<?php

namespace App\Console\Commands;

use App\Enums\UserTypeEnum;
use App\Models\User;
use Illuminate\Console\Command;

class CreateAdminUser extends Command
{
    protected $signature = 'app:admin:new
        {--name= : Admin name (required)}
        {--email= : Admin email for login (required)}
        {--password= : Admin password for login (required)}';

    protected $description = 'Create a new admin user';

    public function handle(): int
    {
        if (! $this->checkAdminLimit()) {
            return Command::FAILURE;
        }

        $name = $this->getNameInput();
        $email = $this->getEmailInput();
        $password = $this->getPasswordInput();

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'user_type' => UserTypeEnum::ADMIN,
        ]);

        $this->displayResults($user);

        return Command::SUCCESS;
    }

    private function checkAdminLimit(): bool
    {
        $maxAdmins = (int) env('MAX_ADMIN_USERS', 1);
        $currentAdminCount = User::where('user_type', UserTypeEnum::ADMIN)->count();

        if ($currentAdminCount >= $maxAdmins) {
            $this->error("Maximum admin limit ({$maxAdmins}) reached. Cannot create more admin users.");

            return false;
        }

        return true;
    }

    private function getNameInput(): string
    {
        $name = $this->option('name');

        if (empty($name)) {
            $name = $this->ask('Please enter the admin name');
        }

        if (empty($name)) {
            $this->error('Admin name is required.');
            exit(Command::FAILURE);
        }

        return $name;
    }

    private function getEmailInput(): string
    {
        $email = $this->option('email');

        if (empty($email)) {
            $email = $this->ask('Please enter the admin email');
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

    private function displayResults(User $user): void
    {
        $this->newLine();
        $this->info('Admin user created successfully!');
        $this->newLine();

        $this->table(
            ['Field', 'Value'],
            [
                ['User ID', $user->id],
                ['Name', $user->name],
                ['Email', $user->email],
                ['User Type', $user->user_type->value],
                ['Created At', $user->created_at->format('Y-m-d H:i:s')],
            ]
        );
    }
}
