<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EntityOnboardingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'email' => 'required|email',
            'surname' => 'string|nullable',
            'forename' => 'string|nullable',
            'countryOfResidence' => 'string|nullable',
            'placeOfBirth' => 'string|nullable',
            'nationality' => 'string|nullable',
            'idIssuingCountry' => 'string|nullable',
            'dateOfBirth' => 'date_format:Y-m-d|nullable',
            'gender' => 'string|nullable',
        ];
    }
}
