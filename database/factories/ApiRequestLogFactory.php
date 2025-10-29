<?php

namespace Database\Factories;

use App\Models\ApiRequestLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiRequestLog>
 */
class ApiRequestLogFactory extends Factory
{
    protected $model = ApiRequestLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $providers = ['regtank', 'glair', 'test'];
        $provider = fake()->randomElement($providers);

        return [
            'request_uuid' => (string) Str::uuid(),
            'provider' => $provider,
            'payload' => json_encode([
                'personal_info' => [
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'date_of_birth' => fake()->date(),
                ],
                'contact' => [
                    'email' => fake()->email(),
                    'phone' => fake()->phoneNumber(),
                ],
                'address' => [
                    'street' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'country' => fake()->countryCode(),
                ],
            ]),
            'response' => json_encode([
                'status' => 'success',
                'reference_id' => 'REF' . fake()->randomNumber(8),
                'timestamp' => now()->toIso8601String(),
                'data' => [
                    'screening_result' => fake()->randomElement(['clear', 'match', 'potential_match']),
                    'risk_score' => fake()->numberBetween(0, 100),
                ],
            ]),
        ];
    }

    /**
     * Indicate that the request is for RegTank provider.
     */
    public function regtank(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'regtank',
            'response' => json_encode([
                'status' => 'completed',
                'reference_id' => 'RT' . fake()->randomNumber(8),
                'matches' => fake()->numberBetween(0, 5),
            ]),
        ]);
    }

    /**
     * Indicate that the request is for Glair AI provider.
     */
    public function glairAI(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'glair',
            'payload' => json_encode([
                'document_type' => fake()->randomElement(['ktp', 'passport']),
                'image_url' => fake()->imageUrl(),
            ]),
            'response' => json_encode([
                'status' => 'success',
                'ocr_result' => [
                    'nik' => fake()->numerify('################'),
                    'name' => fake()->name(),
                    'place_of_birth' => fake()->city(),
                    'date_of_birth' => fake()->date(),
                ],
            ]),
        ]);
    }

    /**
     * Indicate that the request failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'response' => json_encode([
                'status' => 'error',
                'error_code' => fake()->randomElement(['TIMEOUT', 'INVALID_DATA', 'SERVICE_UNAVAILABLE']),
                'error_message' => fake()->sentence(),
            ]),
        ]);
    }

    /**
     * Indicate that the request has no response yet.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'response' => null,
        ]);
    }
}
