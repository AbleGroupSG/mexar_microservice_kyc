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
        $uuid = (string) Str::uuid();

        return [
            'id' => $uuid,
            'user_id' => User::factory(),
            'user_api_key_id' => UserApiKey::factory(),
            'provider' => 'regtank',
            'provider_reference_id' => 'REF' . fake()->randomNumber(6),
            'profile_data' => [
                'uuid' => $uuid,
                'personal_info' => [
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'date_of_birth' => fake()->date(),
                    'nationality' => fake()->countryCode(),
                ],
                'contact' => [
                    'email' => fake()->email(),
                    'phone' => fake()->phoneNumber(),
                ],
                'identification' => [
                    'id_type' => 'national_id',
                    'id_number' => fake()->numerify('################'),
                    'issuing_country' => fake()->countryCode(),
                    'issue_date' => fake()->optional()->date(),
                    'expiry_date' => fake()->optional()->date(),
                ],
                'address' => [
                    'street' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'state' => fake()->state(),
                    'postal_code' => fake()->postcode(),
                    'country' => fake()->countryCode(),
                    'address_line' => fake()->address(),
                ],
                'meta' => [
                    'service_provider' => 'regtank',
                    'reference_id' => 'REF' . fake()->randomNumber(6),
                    'status' => null,
                ],
            ],
            'provider_response_data' => null,
            'status' => KycStatuseEnum::PENDING,
        ];
    }

    /**
     * Indicate that the profile is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KycStatuseEnum::PENDING,
        ]);
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

    /**
     * Indicate that the profile is provider approved (awaiting manual review).
     */
    public function providerApproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KycStatuseEnum::PROVIDER_APPROVED,
        ]);
    }

    /**
     * Indicate that the profile is provider rejected (awaiting manual review).
     */
    public function providerRejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KycStatuseEnum::PROVIDER_REJECTED,
        ]);
    }

    /**
     * Indicate that the profile has provider error (awaiting manual review).
     */
    public function providerError(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KycStatuseEnum::PROVIDER_ERROR,
        ]);
    }

    /**
     * Indicate that the profile has been reviewed by an admin.
     */
    public function reviewed(User $reviewer): static
    {
        return $this->state(fn (array $attributes) => [
            'review_notes' => fake()->sentence(10),
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'provider_status' => fake()->randomElement([
                KycStatuseEnum::PROVIDER_APPROVED,
                KycStatuseEnum::PROVIDER_REJECTED,
                KycStatuseEnum::PROVIDER_ERROR,
            ]),
        ]);
    }
}
