<?php

namespace App\DTO\Webhooks;

class KycDTO
{
    public function __construct(
        public string $requestId,
        public string $referenceId,
        public string $riskScore,
        public string $riskLevel,
        public string $status,
        public string $messageStatus,
        public int    $possibleMatchCount,
        public int    $blacklistedMatchCount,
        public string $assignee,
        public string $timestamp,
    ){}

    public static function make(array $data): static
    {
        return new static (
          requestId: $data['requestId'],
          referenceId: $data['referenceId'],
          riskScore: $data['riskScore'],
          riskLevel: $data['riskLevel'],
          status: $data['status'],
          messageStatus: $data['messageStatus'],
          possibleMatchCount: $data['possibleMatchCount'],
          blacklistedMatchCount: $data['blacklistedMatchCount'],
          assignee: $data['assignee'],
          timestamp: $data['timestamp'],
        );
    }
}
