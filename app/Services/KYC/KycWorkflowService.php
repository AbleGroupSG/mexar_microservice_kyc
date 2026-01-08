<?php

namespace App\Services\KYC;

use App\Enums\KycStatuseEnum;
use App\Jobs\SendKycWebhookJob;
use App\Models\KYCProfile;
use App\Models\User;

class KycWorkflowService
{
    /**
     * Determine the appropriate status based on provider result and manual review setting.
     *
     * If manual review is required, convert final statuses to intermediate PROVIDER_* statuses.
     * Otherwise, return the provider result as-is.
     */
    public function resolveStatus(KYCProfile $profile, KycStatuseEnum $providerResult): KycStatuseEnum
    {
        // If manual review is not required, return the provider result directly
        if (!$profile->needsManualReview()) {
            return $providerResult;
        }

        // Map final statuses to intermediate provider statuses for manual review
        return match ($providerResult) {
            KycStatuseEnum::APPROVED => KycStatuseEnum::PROVIDER_APPROVED,
            KycStatuseEnum::REJECTED => KycStatuseEnum::PROVIDER_REJECTED,
            KycStatuseEnum::ERROR => KycStatuseEnum::PROVIDER_ERROR,
            // PENDING and UNRESOLVED stay as-is
            default => $providerResult,
        };
    }

    /**
     * Determine if webhook should be dispatched based on current profile state.
     *
     * Webhook is dispatched only when:
     * - Manual review is not required, OR
     * - Profile has reached a final status (not awaiting review)
     */
    public function shouldDispatchWebhook(KYCProfile $profile): bool
    {
        // If manual review is not required, always dispatch
        if (!$profile->needsManualReview()) {
            return true;
        }

        // If awaiting review (PROVIDER_* status), don't dispatch yet
        if ($profile->isAwaitingReview()) {
            return false;
        }

        // Final status reached (either after manual review or for non-reviewable statuses)
        return true;
    }

    /**
     * Process a manual review action on a KYC profile.
     *
     * This method:
     * 1. Stores the original provider status if not already stored
     * 2. Updates the profile to the final status
     * 3. Records review metadata (notes, reviewer, timestamp)
     * 4. Dispatches the webhook to notify the client
     */
    public function processReview(
        KYCProfile $profile,
        KycStatuseEnum $finalStatus,
        string $reviewNotes,
        User $reviewer
    ): void {
        // Store original provider status if not already stored
        if (!$profile->provider_status) {
            $profile->provider_status = $profile->status;
        }

        // Update to final status
        $profile->status = $finalStatus;
        $profile->review_notes = $reviewNotes;
        $profile->reviewed_by = $reviewer->id;
        $profile->reviewed_at = now();
        $profile->save();

        // Dispatch webhook to notify client
        SendKycWebhookJob::dispatch(
            profileId: $profile->id,
            additionalData: [
                'review_data' => [
                    'notes' => $reviewNotes,
                    'reviewed_by' => $reviewer->name,
                    'reviewed_at' => $profile->reviewed_at->toIso8601String(),
                    'original_provider_status' => $profile->provider_status->value,
                ],
            ]
        );
    }

    /**
     * Get the display label for a provider status.
     */
    public function getProviderStatusLabel(KycStatuseEnum $status): string
    {
        return match ($status) {
            KycStatuseEnum::PROVIDER_APPROVED => 'Provider Approved (Awaiting Review)',
            KycStatuseEnum::PROVIDER_REJECTED => 'Provider Rejected (Awaiting Review)',
            KycStatuseEnum::PROVIDER_ERROR => 'Provider Error (Awaiting Review)',
            default => ucfirst($status->value),
        };
    }
}
