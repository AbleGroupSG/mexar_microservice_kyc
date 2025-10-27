<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\APIController;
use App\Http\Requests\EntityOnboardingRequest;
use App\Services\IndividualOnboarding\RegtankIndividualOnboardingService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Header;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class EntityOnboardingController extends APIController
{
    public function __construct(
        protected RegtankIndividualOnboardingService $onboardingService
    ) {}

    #[Group("Entity Onboarding")]
    #[Endpoint(
        title: "Submit individual onboarding check",
        description: "Performs compliance screening for individual entity onboarding using RegTank. This endpoint checks individuals against sanctions lists, PEP databases, and adverse media for customer due diligence and compliance purposes."
    )]
    #[Header("X-API-KEY", "Your API key for authentication")]
    #[BodyParam("email", "string", "Email address of the individual (required)", required: true, example: "john.doe@example.com")]
    #[BodyParam("surname", "string", "Surname/Last name of the individual", required: false, example: "Doe")]
    #[BodyParam("forename", "string", "Forename/First name of the individual", required: false, example: "John")]
    #[BodyParam("countryOfResidence", "string", "Country of residence (ISO 3166-1 alpha-2 code)", required: false, example: "SG")]
    #[BodyParam("placeOfBirth", "string", "Place of birth (city or country)", required: false, example: "Singapore")]
    #[BodyParam("nationality", "string", "Nationality (ISO 3166-1 alpha-2 code)", required: false, example: "SG")]
    #[BodyParam("idIssuingCountry", "string", "Country that issued the identification document (ISO 3166-1 alpha-2 code)", required: false, example: "SG")]
    #[BodyParam("dateOfBirth", "string", "Date of birth in YYYY-MM-DD format", required: false, example: "1985-03-15")]
    #[BodyParam("gender", "string", "Gender (Male, Female, or Other)", required: false, example: "Male")]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 200,
                "message": "Screening successful",
                "request_id": "550e8400-e29b-41d4-a716-446655440020"
            },
            "data": {
                "requestId": "REG-789012",
                "status": "pending",
                "email": "john.doe@example.com",
                "name": "John Doe"
            }
        }',
        status: 200,
        description: "Onboarding screening request successfully submitted to RegTank"
    )]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 422,
                "message": "Validation failed",
                "request_id": "550e8400-e29b-41d4-a716-446655440021"
            },
            "errors": {
                "email": ["The email field is required.", "The email must be a valid email address."]
            }
        }',
        status: 422,
        description: "Validation error - email is required and must be valid"
    )]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 500,
                "message": "Screening failed",
                "request_id": "550e8400-e29b-41d4-a716-446655440022"
            },
            "errors": {
                "error": "RegTank API connection failed"
            }
        }',
        status: 500,
        description: "Provider error or internal server error"
    )]
    /**
     * Submit an individual entity onboarding check.
     *
     * This endpoint performs compliance screening for individual onboarding,
     * checking against sanctions lists, PEP databases, and adverse media.
     * Used for customer due diligence (CDD) and enhanced due diligence (EDD).
     *
     * Workflow:
     * 1. Request authenticated via X-API-KEY header
     * 2. Individual data validated (email required)
     * 3. Profile created and sent to RegTank Individual Onboarding API
     * 4. Response returned with RegTank request ID
     * 5. Results delivered via webhook when screening completes
     *
     * Use Cases:
     * - Customer onboarding compliance checks
     * - Beneficial owner verification
     * - Director and shareholder screening
     * - PEP (Politically Exposed Person) checks
     *
     * @param EntityOnboardingRequest $request Validated individual data
     * @return JsonResponse Screening result or error
     */
    public function check(EntityOnboardingRequest $request): JsonResponse
    {
        $data = $request->validated();
        try {
            $response = $this->onboardingService->createProfile($data);
            $response = json_decode($response, true);
            return $this->respondWithWrapper($response, 'Screening successful');
        } catch (HttpException|ConnectionException $e) {
            logger()->error('Entity onboarding request failed', [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->respondWithError([
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR, 'Screening failed');
        } catch (Throwable $e) {
            logger()->error('An error occurred during entity onboarding', [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->respondWithError([
                'error' => 'An internal error happened',
            ], Response::HTTP_INTERNAL_SERVER_ERROR, 'Screening failed');
        }
    }
}
