<?php

namespace Database\Factories;

use App\Enums\KycStatuseEnum;
use App\Models\KYCProfile;
use App\Models\User;
use App\Models\UserApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KYCProfile>
 */
class KYCProfileFactory extends Factory
{
    protected $model = KYCProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();
        $userApiKey = UserApiKey::factory()->create(['user_id' => $user->id]);

        return [
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'user_api_key_id' => $userApiKey->id,
            'provider' => 'regtank',
            'provider_reference_id' => 'REF' . fake()->randomNumber(6),
            'profile_data' => [
                'personal_info' => [
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'date_of_birth' => fake()->date(),
                ],
                'contact' => [
                    'email' => fake()->email(),
                    'phone' => fake()->phoneNumber(),
                ],
            ],
            'provider_response_data' => null,
            'status' => KycStatuseEnum::PENDING,
        ];
    }

    /**
     * Indicate that the profile is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KycStatuseEnum::APPROVED,
        ]);
    }

    /**
     * Indicate that the profile is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KycStatuseEnum::REJECTED,
        ]);
    }

    /**
     * Indicate that the profile has an error.
     */
    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KycStatuseEnum::ERROR,
        ]);
    }

    /**
     * Indicate that the profile uses GlairAI provider.
     */
    public function glairAI(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'glair_ai',
        ]);
    }
}
