<?php

namespace App\Http\Requests;

use App\Enums\KycServiceTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
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
            'personal_info.gender' => ['required', 'string', 'in:Male,Female,Unspecified'],
            'personal_info.date_of_birth' => ['required', 'date', 'date_format:Y-m-d'],
            'personal_info.nationality' => ['required', 'string', 'size:2'],

            'identification' => ['required', 'array'],
            'identification.id_type' => ['required', 'string'],
            'identification.id_number' => ['required', 'string'],
            'identification.issuing_country' => ['required', 'string', 'size:2'],
            'identification.issue_date' => ['required', 'date', 'date_format:Y-m-d'],
            'identification.expiry_date' => ['required', 'date', 'date_format:Y-m-d', 'after:identification.issue_date'],

            'address' => ['required', 'array'],
            'address.street' => ['required', 'string'],
            'address.city' => ['required', 'string'],
            'address.state' => ['required', 'string'],
            'address.postal_code' => ['required', 'string'],
            'address.country' => ['required', 'string', 'size:2'],

            'contact' => ['required', 'array'],
            'contact.email' => ['required', 'email'],
            'contact.phone' => ['required', 'string'],

            'documents' => ['required', 'array'],
            'documents.id_front' => ['required', 'string'],
            'documents.id_back' => ['required', 'string'],
            'documents.passport' => ['required', 'string'],
            'documents.utility_bill' => ['required', 'string'],

            'meta' => ['required', 'array'],
            'meta.service_provider' => ['required', new Enum(KycServiceTypeEnum::class)],
            'meta.reference_id' => ['required',],
        ];
    }
}
