<?php

namespace App\Http\Controllers\API;

use App\DTO\UserDataDTO;
use App\Enums\KycServiceTypeEnum;
use App\Http\Requests\ScreenRequest;
use App\Services\KYC\GlairAIService;
use App\Services\KYC\RegtankService;
use Exception;
use Illuminate\Http\JsonResponse;

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

//        try {
            $response = $service->screen();
            return $this->respondWithWrapper($response, 'Screening successful');
//        } catch (Exception $e) {
//            return $this->respondWithError([
//                'error' => $e->getMessage(),
//            ], $e->getCode(), 'Screening failed');
//        }
    }
}
