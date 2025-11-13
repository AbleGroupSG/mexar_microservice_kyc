<?php

namespace App\Http\Controllers\API;

use App\Enums\KycServiceTypeEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;

/**
 * Provider Management Controller
 *
 * Handles API endpoints for retrieving available KYC service providers.
 * Returns a list of supported providers that can be used in KYC screening requests.
 *
 * @package App\Http\Controllers\API
 */
#[Group("System Information")]
class ProviderController extends APIController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * List all available KYC service providers
     *
     * Returns an array of supported KYC service provider identifiers that can be used
     * in the `meta.service_provider` field when submitting KYC screening requests.
     *
     * Available providers:
     * - `regtank`: RegTank KYC/KYB screening via Dow Jones API
     * - `glair_ai`: GlairAI OCR for Indonesian documents
     * - `test`: Mock provider for testing without calling external APIs
     *
     * @param Request $request The incoming HTTP request
     * @return JsonResponse JSON response containing list of provider identifiers
     */
    #[Endpoint(
        title: "List KYC Providers",
        description: "Retrieve all available KYC service providers"
    )]
    #[Response(
        content: '{"meta": {"code": 200, "message": "Success", "request_id": "uuid"}, "data": ["regtank", "glair_ai", "test"]}',
        status: 200,
        description: "Successful response with provider list"
    )]
    public function listProviders(Request $request): JsonResponse
    {
        return $this->respondWithWrapper(KycServiceTypeEnum::values());
    }
}
