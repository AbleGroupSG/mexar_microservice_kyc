<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserApiKey>
 */
class UserApiKeyFactory extends Factory
{
    protected $model = UserApiKey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true) . ' API',
            'api_key' => Str::random(32),
            'signature_key' => Str::random(64),
            'webhook_url' => fake()->url() . '/webhook',
        ];
    }

    /**
     * Indicate that the API key has no webhook URL.
     */
    public function withoutWebhook(): static
    {
        return $this->state(fn (array $attributes) => [
            'webhook_url' => null,
        ]);
    }

    /**
     * Indicate that the API key has no signature key.
     */
    public function withoutSignature(): static
    {
        return $this->state(fn (array $attributes) => [
            'signature_key' => null,
        ]);
    }
}
