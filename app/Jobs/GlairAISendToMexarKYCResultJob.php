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

class GlairAISendToMexarKYCResultJob implements ShouldQueue
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
                'verified' => $this->status === KycStatuseEnum::APPROVED,
                'verified_at' => Carbon::now(),
                'message' => 'KYC verification process',
                'review_notes' => 'Document and face match confirmed',
                'failure_reason' => $this->error,
            ],
        ];

        Log::info('Sending KYC result to Mexar', [
            'data' => $data,
            'status' => $this->status,
            'error' => $this->error,
        ]);

        $result = Http::post('https://mexar.com/api/kyc', $data);

        if (!$result->successful()) {
            Log::error('Failed to send KYC result to Mexar', [
                'status' => $result->status(),
                'response' => $result->body(),
            ]);
        } else {
            Log::info('KYC result sent to Mexar successfully', [
                'response' => $result->json(),
            ]);
        }
    }
}
