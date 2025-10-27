<?php

namespace App\Http\Controllers\API;

use App\Enums\OcrServiceTypeEnum;
use App\Http\Requests\KycRequest;
use App\Services\KYC\GlairAI\GlairAIService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OCRController extends APIController
{
    public function ocr(KycRequest $request): JsonResponse
    {
        $data = $request->validated();
        $uuid = Str::uuid();
        $this->logRequest($request, $uuid);

        $type = OcrServiceTypeEnum::from($data['document_type']);

        if($type === OcrServiceTypeEnum::KTP){
            try {
                $result = $this->handleKTP($request, $uuid);
                return $this->respondWithWrapper(data: $result, request_id: $uuid);
            }catch (Throwable $e) {
                Log::error('KTP reading failed', ['uuid' => $uuid, 'error' => $e->getMessage()]);
                return $this->respondWithError(
                    errors: ['error' => 'KTP reading failed'],
                    statusCode: Response::HTTP_SERVICE_UNAVAILABLE,
                    message: 'KTP reading failed',
                    request_id: $uuid,
                );
            }
        }

        if($type === OcrServiceTypeEnum::PASSPORT){
            $result = $this->handlePassport($request, $uuid);
            return $this->respondWithWrapper(
                data: $result,
                request_id: $uuid
            );
        }

        return $this->respondWithError(
            errors: ['uuid' => $uuid,'error' => 'Unsupported document type',],
            statusCode: Response::HTTP_BAD_REQUEST,
            message: 'Unsupported document type',
            request_id: $uuid,
        );
    }


    /**
     * @throws ConnectionException
     */
    private function handleKTP(KycRequest $request, $uuid): array
    {
        $service = new GlairAIService();
        if (isset($request->meta['test'])) {
            return $service->formatResult([]);
        }
        $path = $request->file('image')->store('uploads', 'public');
        $result = $service->readOCR(GlairAIService::KTP_URL, storage_path('app/public/'.$path));

        return $service->formatResult($result->toArray());
    }

    /**
     * @throws ConnectionException
     */
    private function handlePassport(KycRequest $request, $uuid): array
    {
        $service = new GlairAIService();
        if (isset($request->meta['test'])) {
            return $service->formatResult([]);
        }
        $path = $request->file('image')->store('uploads', 'public');
        $result = $service->readOCR(GlairAIService::PASSPORT_URL, storage_path('app/public/'.$path));

        return $service->formatResult($result->toArray());
    }

    private function logRequest(KycRequest $request, $uuid): void
    {
        Log::info('OCR Request', [
            'uuid' => $uuid,
            'document_type' => $request->input('document_type'),
            'timestamp' => Carbon::now()->toDateTimeString(),
            'ip_address' => $request->ip(),
            'options' => $request->input('options', []),
        ]);
    }
}
