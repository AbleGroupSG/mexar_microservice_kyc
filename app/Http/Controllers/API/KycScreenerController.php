<?php

namespace App\Http\Controllers\API;

use App\DTO\UserDataDTO;
use App\Enums\KycServiceTypeEnum;
use App\Http\Requests\ScreenRequest;
use App\Services\KYC\GlairAI\GlairAIService;
use App\Services\KYC\Regtank\RegtankService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class KycScreenerController extends APIController
{
    /**
     * @throws \Exception
     */
    public function screen(ScreenRequest $request): JsonResponse
    {
        $uuid = Str::uuid();
        $data = UserDataDTO::from(['uuid' => $uuid, ...$request->validated()]);
        $serviceProvider = $data->meta->service_provider;

        $service = match(KycServiceTypeEnum::from($serviceProvider)) {
            KycServiceTypeEnum::REGTANK => new RegtankService(),
            KycServiceTypeEnum::GLAIR_AI => new GlairAIService(),
        };

        try {
            $response = $service->screen($data);
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
