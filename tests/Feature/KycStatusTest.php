<?php

namespace Tests\Feature;

use App\Enums\KycStatuseEnum;
use App\Models\KYCProfile;
use App\Models\User;
use App\Models\UserApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KycStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_endpoint_returns_profile_data(): void
    {
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create(['user_id' => $user->id]);
        $profile = KYCProfile::factory()->create([
            'status' => KycStatuseEnum::PENDING,
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
        ]);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey->api_key,
        ])->getJson("/api/status/{$profile->id}");

        $response->assertOk()
            ->assertJson([
                'meta' => [
                    'code' => 200,
                    'message' => 'Status retrieved successfully',
                ],
                'data' => [
                    'uuid' => $profile->id,
                    'status' => 'pending',
                    'provider' => $profile->provider,
                ],
            ])
            ->assertJsonStructure([
                'meta' => ['code', 'message', 'request_id'],
                'data' => [
                    'uuid',
                    'status',
                    'provider',
                    'provider_reference_id',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_status_endpoint_returns_404_for_nonexistent_profile(): void
    {
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey->api_key,
        ])->getJson('/api/status/nonexistent-uuid');

        $response->assertNotFound()
            ->assertJson([
                'meta' => [
                    'code' => 404,
                    'message' => 'Profile not found',
                ],
            ]);
    }

    public function test_status_endpoint_shows_updated_status_after_completion(): void
    {
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create(['user_id' => $user->id]);
        $profile = KYCProfile::factory()->approved()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider_reference_id' => 'REF123',
        ]);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey->api_key,
        ])->getJson("/api/status/{$profile->id}");

        $response->assertOk()
            ->assertJson([
                'meta' => [
                    'code' => 200,
                ],
                'data' => [
                    'uuid' => $profile->id,
                    'status' => 'approved',
                    'provider_reference_id' => 'REF123',
                ],
            ]);
    }

    public function test_status_endpoint_requires_authentication(): void
    {
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create(['user_id' => $user->id]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
        ]);

        $response = $this->getJson("/api/status/{$profile->id}");

        $response->assertUnauthorized();
    }

    public function test_status_endpoint_rejects_invalid_api_key(): void
    {
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create(['user_id' => $user->id]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
        ]);

        $response = $this->withHeaders([
            'X-API-KEY' => 'invalid-key',
        ])->getJson("/api/status/{$profile->id}");

        $response->assertUnauthorized();
    }

    public function test_status_endpoint_returns_rejected_status(): void
    {
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create(['user_id' => $user->id]);
        $profile = KYCProfile::factory()->rejected()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
        ]);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey->api_key,
        ])->getJson("/api/status/{$profile->id}");

        $response->assertOk()
            ->assertJson([
                'meta' => [
                    'code' => 200,
                ],
                'data' => [
                    'status' => 'rejected',
                ],
            ]);
    }

    public function test_status_endpoint_returns_error_status(): void
    {
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create(['user_id' => $user->id]);
        $profile = KYCProfile::factory()->error()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
        ]);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey->api_key,
        ])->getJson("/api/status/{$profile->id}");

        $response->assertOk()
            ->assertJson([
                'meta' => [
                    'code' => 200,
                ],
                'data' => [
                    'status' => 'error',
                ],
            ]);
    }
}
