<?php

namespace App\Http\Requests;

use App\Enums\KycServiceTypeEnum;
use App\Enums\KycStatuseEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ScreenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'personal_info' => ['required', 'array'],
            'personal_info.first_name' => ['required', 'string'],
            'personal_info.last_name' => ['required', 'string'],
            'personal_info.gender' => ['sometimes', 'string', 'in:Male,Female,Unspecified'],
            'personal_info.date_of_birth' => ['nullable', 'date', 'date_format:Y-m-d'],
            'personal_info.birth_place' => ['nullable', 'string', 'max:255'],
            'personal_info.nationality' => ['required', 'string', 'size:2'], //cca2,id,sg

            'identification' => ['required', 'array'],
            'identification.id_type' => ['required', 'string'], // national_id, passport
            'identification.id_number' => ['required', 'string'],
            'identification.issuing_country' => ['required', 'string', 'size:2'],
            'identification.issue_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'identification.expiry_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after:identification.issue_date'],

            'address' => ['nullable', 'array'],
            'address.street' => ['nullable', 'string'],
            'address.city' => ['required', 'string'],
            'address.state' => ['nullable', 'string'],
            'address.postal_code' => ['nullable', 'string'],
            'address.country' => ['required', 'string', 'size:2'],
            'address.address_line' => ['required', 'string'],

            'contact' => ['nullable', 'array'],
            'contact.email' => ['nullable', 'email'],
            'contact.phone' => ['nullable', 'string'],

            'documents' => ['nullable', 'array'],
            'documents.photo_selfie' => ['nullable', 'string'],

            'meta' => ['required', 'array'],
            'meta.service_provider' => ['required', 'in:' . implode(',', KycServiceTypeEnum::values())],
            'meta.reference_id' => ['required',],
            'meta.status' => ['nullable', Rule::enum(KycStatuseEnum::class)],
            'meta.test' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->sometimes('personal_info.birth_place', ['required'], function ($input) {
            return ($input->meta['service_provider'] ?? null) === KycServiceTypeEnum::SIJITU->value;
        });

        $validator->sometimes('documents.photo_selfie', ['required'], function ($input) {
            return ($input->meta['service_provider'] ?? null) === KycServiceTypeEnum::SIJITU->value;
        });

        $validator->sometimes('contact.email', ['required'], function ($input) {
            return ($input->meta['service_provider'] ?? null) === KycServiceTypeEnum::SIJITU->value;
        });
    }
}
