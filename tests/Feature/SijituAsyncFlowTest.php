<?php

namespace Tests\Feature;

use App\DTO\UserDataDTO;
use App\Enums\KycStatuseEnum;
use App\Jobs\SendKycWebhookJob;
use App\Jobs\SijituVerificationJob;
use App\Models\KYCProfile;
use App\Models\User;
use App\Models\UserApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SijituAsyncFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('sijitu.url', 'https://sandbox-api.espay.id');
        config()->set('sijitu.authorization', 'Bearer test-token');
        config()->set('sijitu.username', 'sjtpoc0003-sijitu');
        config()->set('sijitu.password', 'secret-password');
        config()->set('sijitu.sender_id', 'SJTPOC0003');
        config()->set('sijitu.organization_id', 'ORG0003');
        config()->set('sijitu.signature_key', '78nCDmceDx4sy74p');
    }

    private function getValidScreeningData(): array
    {
        return [
            'personal_info' => [
                'first_name' => 'Yobi',
                'last_name' => 'Setiawan',
                'date_of_birth' => '1980-01-01',
                'birth_place' => 'Jakarta',
                'nationality' => 'ID',
            ],
            'identification' => [
                'id_type' => 'national_id',
                'id_number' => '3511000101806300',
                'issuing_country' => 'ID',
            ],
            'address' => [
                'city' => 'Jakarta',
                'country' => 'ID',
                'address_line' => 'Jl. Test No. 123',
            ],
            'contact' => [
                'email' => 'poc_sijitu_appui_03@outlook.com',
            ],
            'documents' => [
                'photo_selfie' => 'data:image/jpeg;base64,' . base64_encode('fake-selfie'),
            ],
            'meta' => [
                'service_provider' => 'sijitu',
                'reference_id' => 'REF123',
            ],
        ];
    }

    public function test_sijitu_screen_dispatches_verification_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey->api_key,
        ])->postJson('/api/v1/screen', $this->getValidScreeningData());

        $response->assertOk()
            ->assertJsonStructure([
                'meta' => ['code', 'message', 'request_id'],
                'data' => ['identity'],
            ]);

        Queue::assertPushed(SijituVerificationJob::class);
    }

    public function test_sijitu_verification_job_marks_profile_approved_and_sends_webhook(): void
    {
        Queue::fake();
        Http::fake([
            'https://sandbox-api.espay.id/cdd/sijitu/biometric' => Http::response([
                'rq_uuid' => 'uuid-1',
                'error_code' => '0000',
                'error_message' => 'Success',
                'verification_name' => 'Verified',
                'verification_birthdate' => 'Verified',
                'verification_nik' => 'Verified',
                'rate_selfie_photo' => 'Verified',
                'rate_liveness' => 'Verified',
                'reason' => 'Verified',
            ], 200),
        ]);

        [$profile, $dto, $payload] = $this->makeProfileAndPayload('uuid-1');

        $job = new SijituVerificationJob($profile->id, $dto, $payload);
        $job->handle();

        $profile->refresh();

        $this->assertEquals(KycStatuseEnum::APPROVED, $profile->status);
        $this->assertEquals('uuid-1', $profile->provider_reference_id);

        Http::assertSent(function ($request) use ($payload) {
            $requestData = $request->data();
            $format = sprintf(
                '##%s##%s##%s##%s##%s##%s##',
                $payload['rq_uuid'],
                $payload['sender_id'],
                $payload['user_id'],
                $payload['nomor_identitas'],
                'BIOMETRIC',
                config('sijitu.signature_key')
            );
            $expectedSignature = hash('sha256', strtoupper($format));

            return $request->url() === 'https://sandbox-api.espay.id/cdd/sijitu/biometric'
                && ($requestData['signature'] ?? null) === $expectedSignature;
        });

        Queue::assertPushed(SendKycWebhookJob::class);
    }

    public function test_sijitu_verification_job_marks_profile_rejected_when_any_check_fails(): void
    {
        Queue::fake();
        Http::fake([
            'https://sandbox-api.espay.id/cdd/sijitu/biometric' => Http::response([
                'rq_uuid' => 'uuid-2',
                'error_code' => '0000',
                'error_message' => 'Success',
                'verification_name' => 'Verified',
                'verification_birthdate' => 'Verified',
                'verification_nik' => 'Verified',
                'rate_selfie_photo' => 'Verified',
                'rate_liveness' => 'Not Verified',
                'reason' => 'Liveness failed',
            ], 200),
        ]);

        [$profile, $dto, $payload] = $this->makeProfileAndPayload('uuid-2');

        $job = new SijituVerificationJob($profile->id, $dto, $payload);
        $job->handle();

        $profile->refresh();

        $this->assertEquals(KycStatuseEnum::REJECTED, $profile->status);
        Queue::assertPushed(SendKycWebhookJob::class);
    }

    public function test_sijitu_verification_job_marks_profile_error_on_provider_error_code(): void
    {
        Queue::fake();
        Http::fake([
            'https://sandbox-api.espay.id/cdd/sijitu/biometric' => Http::response([
                'rq_uuid' => 'uuid-3',
                'error_code' => '1001',
                'error_message' => 'Unauthorized',
            ], 200),
        ]);

        [$profile, $dto, $payload] = $this->makeProfileAndPayload('uuid-3');

        $job = new SijituVerificationJob($profile->id, $dto, $payload);
        $job->handle();

        $profile->refresh();

        $this->assertEquals(KycStatuseEnum::ERROR, $profile->status);
        Queue::assertPushed(SendKycWebhookJob::class);
    }

    private function makeProfileAndPayload(string $uuid): array
    {
        $payload = $this->getValidScreeningData();
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create(['user_id' => $user->id, 'need_manual_review' => false]);

        $dto = UserDataDTO::from([
            'uuid' => $uuid,
            ...$payload,
        ]);

        $profile = KYCProfile::create([
            'id' => $uuid,
            'profile_data' => $dto->toArray(),
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'sijitu',
            'status' => KycStatuseEnum::PENDING,
        ]);

        return [$profile, $dto, [
            'rq_uuid' => $uuid,
            'rq_datetime' => now()->format('Y-m-d H:i:s'),
            'sender_id' => config('sijitu.sender_id'),
            'user_id' => $payload['contact']['email'],
            'organization_id' => config('sijitu.organization_id'),
            'nama_lengkap' => 'Yobi Setiawan',
            'nomor_identitas' => $payload['identification']['id_number'],
            'address' => $payload['address']['address_line'],
            'birth_date' => '01-Jan-1980',
            'birth_place' => $payload['personal_info']['birth_place'],
            'photo_selfie' => $payload['documents']['photo_selfie'],
        ]];
    }
}
