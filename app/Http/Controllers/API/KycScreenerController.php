<?php

namespace App\Http\Controllers\API;

use App\DTO\UserDataDTO;
use App\Enums\KycServiceTypeEnum;
use App\Http\Requests\ScreenRequest;
use App\Models\KYCProfile;
use App\Services\KYC\KYCServiceFactory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Header;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\UrlParam;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class KycScreenerController extends APIController
{
    #[Group("KYC Screening")]
    #[Endpoint(
        title: "Submit KYC screening request",
        description: "Initiates an asynchronous KYC (Know Your Customer) screening process. This endpoint creates a KYC profile with PENDING status and returns a reference ID immediately. The actual verification is processed asynchronously by the selected provider (RegTank or GlairAI). Once processing completes, a webhook notification will be sent to your configured webhook URL, and you can also poll the status endpoint."
    )]
    #[Header("X-API-KEY", "Your API key for authentication")]
    #[BodyParam("personal_info", "object", "Personal information of the individual being screened", required: true)]
    #[BodyParam("personal_info.first_name", "string", "First name", required: true, example: "John")]
    #[BodyParam("personal_info.last_name", "string", "Last name", required: true, example: "Doe")]
    #[BodyParam("personal_info.date_of_birth", "string", "Date of birth in YYYY-MM-DD format", required: false, example: "1990-01-15")]
    #[BodyParam("personal_info.gender", "string", "Gender (Male, Female, or Unspecified)", required: false, example: "Male")]
    #[BodyParam("personal_info.nationality", "string", "ISO 3166-1 alpha-2 country code (2 letters)", required: true, example: "ID")]
    #[BodyParam("identification", "object", "Identification document information", required: true)]
    #[BodyParam("identification.id_type", "string", "Type of identification document (e.g., national_id, passport)", required: true, example: "national_id")]
    #[BodyParam("identification.id_number", "string", "Identification document number", required: true, example: "1234567890123456")]
    #[BodyParam("identification.issuing_country", "string", "ISO 3166-1 alpha-2 country code of issuing country", required: true, example: "ID")]
    #[BodyParam("identification.issue_date", "string", "Issue date in YYYY-MM-DD format", required: false, example: "2020-01-01")]
    #[BodyParam("identification.expiry_date", "string", "Expiry date in YYYY-MM-DD format (must be after issue_date)", required: false, example: "2030-01-01")]
    #[BodyParam("address", "object", "Address information", required: false)]
    #[BodyParam("address.address_line", "string", "Full address line", required: true, example: "Jl. Sudirman No. 123")]
    #[BodyParam("address.city", "string", "City name", required: true, example: "Jakarta")]
    #[BodyParam("address.state", "string", "State or province", required: false, example: "DKI Jakarta")]
    #[BodyParam("address.postal_code", "string", "Postal or ZIP code", required: false, example: "12190")]
    #[BodyParam("address.country", "string", "ISO 3166-1 alpha-2 country code", required: true, example: "ID")]
    #[BodyParam("address.street", "string", "Street name", required: false, example: "Jl. Sudirman")]
    #[BodyParam("contact", "object", "Contact information", required: false)]
    #[BodyParam("contact.email", "string", "Email address", required: false, example: "john.doe@example.com")]
    #[BodyParam("contact.phone", "string", "Phone number", required: false, example: "+62812345678")]
    #[BodyParam("meta", "object", "Metadata for the screening request", required: true)]
    #[BodyParam("meta.service_provider", "string", "KYC service provider to use (regtank, glair, or test)", required: true, example: "regtank")]
    #[BodyParam("meta.reference_id", "string", "Your internal reference ID for tracking this request", required: true, example: "YOUR-REF-123")]
    #[BodyParam("meta.status", "string", "Optional status override (for internal use)", required: false)]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 200,
                "message": "Screening successful",
                "request_id": "550e8400-e29b-41d4-a716-446655440000"
            },
            "data": {
                "identity": "550e8400-e29b-41d4-a716-446655440000"
            }
        }',
        status: 200,
        description: "KYC screening request accepted. The identity field contains the UUID for status polling. Use GET /api/status/{uuid} to check the screening status, or wait for webhook notification to your configured webhook URL."
    )]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 422,
                "message": "Validation failed",
                "request_id": "550e8400-e29b-41d4-a716-446655440001"
            },
            "errors": {
                "personal_info.nationality": [
                    "The personal info.nationality field is required."
                ],
                "identification.id_type": [
                    "The identification.id type field is required."
                ]
            }
        }',
        status: 422,
        description: "Validation error - required fields are missing or invalid"
    )]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 401,
                "message": "Unauthorized",
                "request_id": "550e8400-e29b-41d4-a716-446655440002"
            },
            "errors": {
                "error": "Unauthorized"
            }
        }',
        status: 401,
        description: "Authentication failed - invalid or missing X-API-KEY header"
    )]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 400,
                "message": "Screening failed",
                "request_id": "550e8400-e29b-41d4-a716-446655440003"
            },
            "errors": {
                "error": "Provider connection failed"
            }
        }',
        status: 400,
        description: "Provider error - the KYC provider returned an error or is unavailable"
    )]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 500,
                "message": "Screening failed",
                "request_id": "550e8400-e29b-41d4-a716-446655440004"
            },
            "errors": {
                "error": "An internal error happened"
            }
        }',
        status: 500,
        description: "Internal server error - an unexpected error occurred during processing"
    )]
    /**
     * Submit a KYC screening request.
     *
     * Workflow:
     * 1. Request is authenticated via X-API-KEY header (VerifyApiKey middleware)
     * 2. Request data is validated against ScreenRequest rules
     * 3. A UUID is generated for the KYC profile
     * 4. Data is transformed into UserDataDTO
     * 5. KYC service is selected based on meta.service_provider (via KYCServiceFactory)
     * 6. Service creates a KYC profile with PENDING status
     * 7. Service initiates async screening with the provider
     * 8. Response returns immediately with identity UUID
     * 9. Provider processes screening asynchronously
     * 10. When complete, webhook is sent to your configured webhook_url
     * 11. Client can poll GET /api/status/{uuid} to check status
     *
     * @param ScreenRequest $request Validated request containing KYC data
     * @return JsonResponse Response with identity UUID or error details
     * @throws \Exception If service creation fails
     */
    public function screen(ScreenRequest $request): JsonResponse
    {
        $uuid = Str::uuid();
        $data = UserDataDTO::from(['uuid' => $uuid, ...$request->validated()]);
        $user = $request->user();
        $userApiKey = $request->attributes->get('user_api_key');
        $serviceProvider = $data->meta->service_provider;
        logger()->debug('KYC screening request', [
            'uuid' => $uuid,
            'user_id' => $user->id,
            'user_api_key_id' => $userApiKey->id,
            'service_provider' => $serviceProvider,
            'data' => $data->toArray(),
        ]);
        $service = KYCServiceFactory::make(
            KycServiceTypeEnum::from($serviceProvider)
        );

        try {
            $response = $service->screen($data, $user, $userApiKey);
            return $this->respondWithWrapper($response, 'Screening successful');
        } catch (HttpException|ConnectionException $e) {
            return $this->respondWithError([
                'error' => $e->getMessage(),
            ], $e->getStatusCode() ?? Response::HTTP_BAD_REQUEST, 'Screening failed');
        } catch (\Throwable $e) {
            return $this->respondWithError([
                'error' => 'An internal error happened',
            ], Response::HTTP_INTERNAL_SERVER_ERROR, 'Screening failed');
        }
    }

    #[Group("KYC Screening")]
    #[Endpoint(
        title: "Get KYC screening status",
        description: "Retrieve the current status of a KYC screening request by UUID. Use this endpoint to poll for screening results after submitting a KYC request. The status will be 'pending' initially, then change to 'approved', 'rejected', or 'error' once the provider completes processing."
    )]
    #[Header("X-API-KEY", "Your API key for authentication")]
    #[UrlParam("uuid", "string", "The UUID of the KYC profile returned from the screening request", example: "550e8400-e29b-41d4-a716-446655440000")]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 200,
                "message": "Status retrieved successfully",
                "request_id": "550e8400-e29b-41d4-a716-446655440005"
            },
            "data": {
                "uuid": "550e8400-e29b-41d4-a716-446655440000",
                "status": "pending",
                "provider": "regtank",
                "provider_reference_id": "REF123456",
                "created_at": "2025-01-15T10:30:00.000000Z",
                "updated_at": "2025-01-15T10:30:00.000000Z"
            }
        }',
        status: 200,
        description: "Status retrieved successfully. Status values: pending (processing), approved (verified), rejected (failed verification), error (provider error)"
    )]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 200,
                "message": "Status retrieved successfully",
                "request_id": "550e8400-e29b-41d4-a716-446655440006"
            },
            "data": {
                "uuid": "550e8400-e29b-41d4-a716-446655440000",
                "status": "approved",
                "provider": "regtank",
                "provider_reference_id": "REF123456",
                "created_at": "2025-01-15T10:30:00.000000Z",
                "updated_at": "2025-01-15T10:35:00.000000Z"
            }
        }',
        status: 200,
        description: "Example of approved status - verification passed"
    )]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 404,
                "message": "Profile not found",
                "request_id": "550e8400-e29b-41d4-a716-446655440007"
            },
            "errors": {
                "error": "Profile not found"
            }
        }',
        status: 404,
        description: "Profile not found - invalid UUID or profile does not exist"
    )]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 401,
                "message": "Unauthorized",
                "request_id": "550e8400-e29b-41d4-a716-446655440008"
            },
            "errors": {
                "error": "Unauthorized"
            }
        }',
        status: 401,
        description: "Authentication failed - invalid or missing X-API-KEY header"
    )]
    /**
     * Get the status of a KYC screening request.
     *
     * This endpoint allows you to poll for the current status of a KYC screening
     * that was previously submitted. The status will progress from PENDING to
     * either APPROVED, REJECTED, or ERROR depending on the verification results.
     *
     * Status Values:
     * - PENDING: Screening is in progress
     * - APPROVED: Identity verification passed
     * - REJECTED: Identity verification failed (e.g., mismatch, watchlist hit)
     * - ERROR: Technical error occurred during processing
     *
     * Polling Strategy:
     * - Initial poll after 5-10 seconds
     * - Subsequent polls every 10-30 seconds
     * - Stop polling once status changes from PENDING
     * - Alternatively, wait for webhook notification (recommended)
     *
     * @param string $uuid The UUID of the KYC profile
     * @return JsonResponse Profile status details or error
     */
    public function status(string $uuid): JsonResponse
    {
        $profile = KYCProfile::find($uuid);

        if (!$profile) {
            return $this->respondWithError(
                ['error' => 'Profile not found'],
                Response::HTTP_NOT_FOUND,
                'Profile not found'
            );
        }

        return $this->respondWithWrapper([
            'uuid' => $profile->id,
            'status' => $profile->status,
            'provider' => $profile->provider,
            'provider_reference_id' => $profile->provider_reference_id,
            'created_at' => $profile->created_at,
            'updated_at' => $profile->updated_at,
        ], 'Status retrieved successfully');
    }
}
