<?php

namespace App\Http\Requests;

use App\Enums\OcrServiceTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KycRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::enum(OcrServiceTypeEnum::class)],
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'options' => 'array',
            'options.enhance' => 'nullable|boolean',
            'options.lang' => 'nullable|string',
            'options.detect_orientation' => 'nullable|boolean',
        ];
    }
}
