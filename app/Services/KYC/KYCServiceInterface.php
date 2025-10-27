<?php

namespace App\Services\KYC;

use App\DTO\UserDataDTO;
use App\Models\User;
use App\Models\UserApiKey;

interface KYCServiceInterface
{
    /**
     * Screen user and create KYC profile with PENDING status.
     * Returns reference ID only for polling.
     *
     * Expected Workflow:
     * 1. Create profile with PENDING status
     * 2. Initiate screening with provider (sync or async)
     * 3. Return reference ID immediately
     * 4. Provider sends webhook or job processes result asynchronously
     * 5. Profile status updated to APPROVED/REJECTED/ERROR
     * 6. Webhook sent to client's configured webhook URL (profile->apiKey->webhook_url)
     *
     * Response Format (Standardized):
     * All implementations MUST return: ['identity' => string]
     * - The 'identity' key contains the profile UUID for status polling
     * - No other data should be returned in the initial response
     * - Clients should poll /api/status/{uuid} or wait for webhook notification
     *
     * @param UserDataDTO $userDataDTO User data for screening
     * @param User $user Authenticated user making the request
     * @param UserApiKey $userApiKey The API key used for this request (contains webhook_url)
     * @return array{identity: string} Reference ID for status polling
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Exception
     */
    public function screen(UserDataDTO $userDataDTO, User $user, UserApiKey $userApiKey): array;

}
