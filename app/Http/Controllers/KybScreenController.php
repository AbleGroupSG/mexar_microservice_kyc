<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\APIController;
use App\Http\Requests\EFormKybScreenRequest;
use App\Services\KYB\RegtankDowJoneKYBService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
