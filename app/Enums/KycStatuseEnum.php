<?php

namespace App\Enums;

enum KycStatuseEnum: string
{
    case PENDING = "pending";
    case REJECTED = "rejected";
    case APPROVED = "approved";
    case ERROR = "error";
    case UNRESOLVED = "unresolved";

    // Intermediate statuses for manual review workflow
    case PROVIDER_APPROVED = "provider_approved";
    case PROVIDER_REJECTED = "provider_rejected";
    case PROVIDER_ERROR = "provider_error";

    public static function getValues(): array
    {
        return [
            self::PENDING,
            self::REJECTED,
            self::APPROVED,
            self::ERROR,
            self::UNRESOLVED,
        ];
    }

    /**
     * Get all statuses including manual review statuses.
     */
    public static function getAllValues(): array
    {
        return [
            self::PENDING,
            self::REJECTED,
            self::APPROVED,
            self::ERROR,
            self::UNRESOLVED,
            self::PROVIDER_APPROVED,
            self::PROVIDER_REJECTED,
            self::PROVIDER_ERROR,
        ];
    }

    /**
     * Check if this status is awaiting manual review.
     */
    public function isAwaitingReview(): bool
    {
        return in_array($this, [
            self::PROVIDER_APPROVED,
            self::PROVIDER_REJECTED,
            self::PROVIDER_ERROR,
        ]);
    }

    /**
     * Get the intermediate provider status for a final status.
     */
    public static function getProviderStatus(self $finalStatus): ?self
    {
        return match ($finalStatus) {
            self::APPROVED => self::PROVIDER_APPROVED,
            self::REJECTED => self::PROVIDER_REJECTED,
            self::ERROR => self::PROVIDER_ERROR,
            default => null,
        };
    }

    /**
     * Get statuses that are awaiting manual review.
     */
    public static function awaitingReviewStatuses(): array
    {
        return [
            self::PROVIDER_APPROVED,
            self::PROVIDER_REJECTED,
            self::PROVIDER_ERROR,
        ];
    }
}
