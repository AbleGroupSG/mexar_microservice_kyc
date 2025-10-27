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
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Header;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OCRController extends APIController
{
    #[Group("OCR Services")]
    #[Endpoint(
        title: "Process OCR for identity documents",
        description: "Extracts text and data from Indonesian identity documents (KTP or Passport) using GlairAI OCR service. This endpoint processes uploaded document images and returns structured data including personal information, document numbers, and validity dates."
    )]
    #[Header("Authorization", "JWT Bearer token for authentication")]
    #[BodyParam("document_type", "string", "Type of document to process (KTP or PASSPORT)", required: true, example: "KTP")]
    #[BodyParam("image", "file", "Image file of the document (jpeg, png, jpg, gif, svg). Max size: 2MB", required: true)]
    #[BodyParam("options", "object", "Optional OCR processing options", required: false)]
    #[BodyParam("options.enhance", "boolean", "Enable image enhancement for better OCR accuracy", required: false, example: true)]
    #[BodyParam("options.lang", "string", "Language code for OCR (e.g., 'id' for Indonesian)", required: false, example: "id")]
    #[BodyParam("options.detect_orientation", "boolean", "Automatically detect and correct document orientation", required: false, example: true)]
    #[BodyParam("meta", "object", "Metadata for the request", required: false)]
    #[BodyParam("meta.test", "boolean", "Set to true to return mock data without calling GlairAI (for testing)", required: false, example: false)]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 200,
                "message": "Success",
                "request_id": "550e8400-e29b-41d4-a716-446655440030"
            },
            "data": {
                "fields": {
                    "national_id_number": "3275012345670001",
                    "full_name": "BUDI SANTOSO",
                    "place_of_birth": "JAKARTA",
                    "date_of_birth": "1990-01-15",
                    "gender": "male",
                    "address": "JL. SUDIRMAN NO. 123",
                    "rt_rw": "001/002",
                    "village": "MENTENG",
                    "district": "MENTENG",
                    "religion": "islam",
                    "marital_status": "married",
                    "occupation": "private employee",
                    "citizenship": "indonesian",
                    "valid_until": "lifetime"
                }
            }
        }',
        status: 200,
        description: "Successfully extracted data from KTP document"
    )]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 200,
                "message": "Success",
                "request_id": "550e8400-e29b-41d4-a716-446655440031"
            },
            "data": {
                "fields": {
                    "passport_number": "A12345678",
                    "full_name": "SITI RAHAYU",
                    "nationality": "INDONESIA",
                    "date_of_birth": "1985-05-20",
                    "place_of_birth": "BANDUNG",
                    "gender": "female",
                    "issue_date": "2020-01-15",
                    "expiry_date": "2025-01-14"
                }
            }
        }',
        status: 200,
        description: "Successfully extracted data from Passport document"
    )]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 422,
                "message": "Validation failed",
                "request_id": "550e8400-e29b-41d4-a716-446655440032"
            },
            "errors": {
                "document_type": ["The document type field is required."],
                "image": ["The image field is required.", "The image must be a file of type: jpeg, png, jpg, gif, svg."]
            }
        }',
        status: 422,
        description: "Validation error - missing or invalid fields"
    )]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 503,
                "message": "KTP reading failed",
                "request_id": "550e8400-e29b-41d4-a716-446655440033"
            },
            "errors": {
                "error": "KTP reading failed"
            }
        }',
        status: 503,
        description: "OCR service unavailable or failed to process document"
    )]
    #[ScribeResponse(
        content: '{
            "meta": {
                "code": 400,
                "message": "Unsupported document type",
                "request_id": "550e8400-e29b-41d4-a716-446655440034"
            },
            "errors": {
                "error": "Unsupported document type"
            }
        }',
        status: 400,
        description: "Invalid document type - only KTP and PASSPORT are supported"
    )]
    /**
     * Process OCR for identity documents.
     *
     * This endpoint extracts structured data from Indonesian identity documents
     * using GlairAI's OCR service. It supports two document types:
     * - KTP (Kartu Tanda Penduduk): Indonesian national ID card
     * - PASSPORT: Indonesian passport
     *
     * Workflow:
     * 1. Request authenticated via JWT Bearer token (VerifyJwtMiddleware)
     * 2. Document image and type validated
     * 3. Image uploaded to storage/app/public/uploads
     * 4. OCR processing performed by GlairAI service
     * 5. Extracted data formatted and returned
     * 6. Job dispatched to send OCR result to MEXAR application
     *
     * Supported Document Types:
     * - KTP: Extracts NIK, name, DOB, address, religion, marital status, etc.
     * - PASSPORT: Extracts passport number, name, nationality, dates, etc.
     *
     * Image Requirements:
     * - Format: JPEG, PNG, JPG, GIF, SVG
     * - Max size: 2MB
     * - Clear, well-lit image with all text visible
     * - Document should be flat (not folded or bent)
     *
     * @param KycRequest $request Validated OCR request with document image
     * @return JsonResponse Extracted document data or error
     */
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
