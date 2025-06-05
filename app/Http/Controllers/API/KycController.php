<?php

namespace App\Http\Controllers\API;

use App\Enums\KycServiceTypeEnumV2;
use App\Http\Requests\KycRequest;
use App\Services\KYC\GlairAI\GlairAIService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class KycController extends APIController
{
    public function kyc(KycRequest $request): JsonResponse
    {
        $data = $request->validated();
        $uuid = Str::uuid();
        $this->logRequest($request, $uuid);

        $type = KycServiceTypeEnumV2::from($data['document_type']);

        if($type === KycServiceTypeEnumV2::KTP){
            try {
                $result = $this->handleKTP($request);
                return $this->respondWithWrapper(data: $result, request_id: $uuid);
            }catch (Throwable $e) {
                Log::error('KTP reading failed', ['uuid' => $uuid, 'error' => $e->getMessage()]);
                return $this->respondWithError(
                    errors: ['error' => 'KTP reading failed'],
                    statusCode: Response::HTTP_SERVICE_UNAVAILABLE,
                    message: 'KTP reading failed'
                );
            }
        }

        if($type === KycServiceTypeEnumV2::PASSPORT){
            //TODO ...
        }
        return $this->respondWithError(
            errors: ['uuid' => $uuid,'error' => 'Unsupported document type',],
            statusCode: Response::HTTP_BAD_REQUEST,
            message: 'Unsupported document type'
        );
    }


    /**
     * @throws ConnectionException
     */
    private function handleKTP(KycRequest $request): array
    {
        $service = new GlairAIService();
        $path = $request->file('image')->store('uploads', 'public');
        $result =  $service->readKTP(storage_path('app/public/'.$path));
        return $service->formatKtpResult($result->toArray());
    }

    private function logRequest(KycRequest $request, $uuid): void
    {
        Log::info('KYC Request', [
            'uuid' => $uuid,
            'document_type' => $request->input('document_type'),
            'timestamp' => Carbon::now()->toDateTimeString(),
            'ip_address' => $request->ip(),
            'options' => $request->input('options', []),
        ]);
    }
}
