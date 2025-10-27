<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\APIController;
use App\Http\Requests\EFormKybScreenRequest;
use App\Services\KYB\RegtankDowJoneKYBService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Header;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class KybScreenController extends APIController
{
    public function __construct(
        protected RegtankDowJoneKYBService $service
    )
    {
    }

    #[Group("KYB Screening")]
    #[Endpoint(
        title: "Submit KYB screening request",
        description: "Initiates a KYB (Know Your Business) screening process for company verification using RegTank's Dow Jones database. This endpoint screens businesses against sanctions lists, adverse media, and politically exposed persons databases."
    )]
    #[Header("X-API-KEY", "Your API key for authentication")]
    #[BodyParam("referenceId", "string", "Your internal reference ID for tracking this business screening request", required: true, example: "COMPANY-REF-123")]
    #[BodyParam("businessName", "string", "Legal name of the business entity", required: true, example: "Acme Corporation Ltd")]
    #[BodyParam("businessIdNumber", "string", "Business registration or tax identification number", required: true, example: "123456789")]
    #[BodyParam("address1", "string", "Primary business address", required: false, example: "123 Business Street, Suite 100")]
    #[BodyParam("email", "string", "Business contact email address", required: false, example: "contact@acme.com")]
    #[BodyParam("phone", "string", "Business contact phone number", required: false, example: "+1-555-0123")]
    #[BodyParam("website", "string", "Business website URL", required: false, example: "https://www.acme.com")]
    #[BodyParam("dateOfIncorporation", "string", "Date of business incorporation in YYYY-MM-DD format", required: false, example: "2010-05-15")]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 200,
                "message": "Screening successful",
                "request_id": "550e8400-e29b-41d4-a716-446655440010"
            },
            "data": {
                "requestId": "REG-123456",
                "status": "pending",
                "businessName": "Acme Corporation Ltd"
            }
        }',
        status: 200,
        description: "KYB screening request successfully submitted to RegTank"
    )]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 422,
                "message": "Validation failed",
                "request_id": "550e8400-e29b-41d4-a716-446655440011"
            },
            "errors": {
                "referenceId": ["The reference id field is required."],
                "businessName": ["The business name field is required."],
                "businessIdNumber": ["The business id number field is required."]
            }
        }',
        status: 422,
        description: "Validation error - required fields missing"
    )]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 400,
                "message": "Screening failed",
                "request_id": "550e8400-e29b-41d4-a716-446655440012"
            },
            "errors": {
                "error": "Provider connection failed"
            }
        }',
        status: 400,
        description: "RegTank provider error"
    )]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 500,
                "message": "Screening failed",
                "request_id": "550e8400-e29b-41d4-a716-446655440013"
            },
            "errors": {
                "error": "An internal error happened"
            }
        }',
        status: 500,
        description: "Internal server error"
    )]
    /**
     * Submit a KYB (Know Your Business) screening request.
     *
     * This endpoint screens business entities against RegTank's Dow Jones database
     * which includes sanctions lists, watchlists, adverse media, and PEP databases.
     *
     * Workflow:
     * 1. Request authenticated via X-API-KEY header
     * 2. Business data validated
     * 3. Profile created in company_kybs table
     * 4. Screening request sent to RegTank Dow Jones API
     * 5. Response returned with RegTank request ID
     * 6. Results delivered via webhook when screening completes
     *
     * @param EFormKybScreenRequest $request Validated KYB request data
     * @return JsonResponse Screening result or error
     */
    public function kyb(EFormKybScreenRequest $request): JsonResponse
    {
        $data = $request->validated();
        try {
            $response = $this->service->createProfile($data);
            Log::warning('response', $data);
            return $this->respondWithWrapper($response, 'Screening successful');
        } catch (HttpException|ConnectionException $e) {

            return $this->respondWithError([
                'error' => $e->getMessage(),
            ], $e->getStatusCode() ?? Response::HTTP_BAD_REQUEST, 'Screening failed');
        } catch (Throwable $e) {
            return $this->respondWithError([
                'error' => 'An internal error happened',
            ], Response::HTTP_INTERNAL_SERVER_ERROR, 'Screening failed');
        }
    }

}
