<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\APIController;
use App\Http\Requests\EntityOnboardingRequest;
use App\Services\IndividualOnboarding\RegtankIndividualOnboardingService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class EntityOnboardingController extends APIController
{
    public function __construct(
        protected RegtankIndividualOnboardingService $onboardingService
    ) {}

    public function check(EntityOnboardingRequest $request): JsonResponse
    {
        $data = $request->validated();
        try {
            $response = $this->onboardingService->createProfile($data);
            $response = json_decode($response, true);
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
