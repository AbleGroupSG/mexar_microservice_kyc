<?php

namespace App\Jobs;

use App\DTO\UserDataDTO;
use App\Enums\KycServiceTypeEnum;
use App\Enums\KycStatuseEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestKYCResultJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected UserDataDTO $userDataDTO,
        protected ?KycStatuseEnum $status,
        protected ?string $error = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->sendToMexar();
    }

    private function sendToMexar(): void
    {
        $data = [
            'event' => 'kyc.status.changed',
            'payload' => [
                'msa_reference_id' => $this->userDataDTO->uuid,
                'provider_reference_id' => $this->userDataDTO->uuid,
                'reference_id' => $this->userDataDTO->uuid,
                'platform' => KycServiceTypeEnum::GLAIR_AI,
                'status' => $this->status,
                'verified_at' => $this->status === KycStatuseEnum::APPROVED ? Carbon::now() : null,
                'rejected_at' => $this->status === KycStatuseEnum::REJECTED ? Carbon::now() : null,
                'message' => 'KYC verification process',
                'review_notes' => null,
                'failure_reason' => $this->error,
            ],
        ];

        $url = config('app.mexar.url');
        $response = Http::post("$url/api/v1/webhook?platform=kyc", $data);

        if (!$response->successful()) {
            Log::error('Failed to send KYC result to Mexar', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
        } else {
            Log::info('KYC result sent to Mexar successfully', [
                'response' => $response->json(),
            ]);
        }
    }
}
