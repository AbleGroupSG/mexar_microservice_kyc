<?php

namespace App\Http\Controllers\API;

use App\DTO\UserDataDTO;
use App\Enums\KycServiceTypeEnum;
use App\Http\Requests\ScreenRequest;
use App\Services\KYC\GlairAIService;
use App\Services\KYC\RegtankService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Client\ConnectionException;

class KycScreenerController extends APIController
{
    public function screen(ScreenRequest $request): JsonResponse
    {
        $data = UserDataDTO::from($request->validated());
        $serviceProvider = $data->meta->service_provider;

        $service = match(KycServiceTypeEnum::from($serviceProvider)) {
            KycServiceTypeEnum::REGTANK => new RegtankService($data),
            KycServiceTypeEnum::GLAIR_AI => new GlairAIService($data),
        };

        try {
            $response = $service->screen();

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
}
