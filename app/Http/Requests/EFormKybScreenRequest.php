<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EFormKybScreenRequest extends FormRequest
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
            'referenceId' => 'required',
            'businessName' => 'required',
            'businessIdNumber' => 'required',
            'address1' => 'nullable',
            'email' => 'nullable',
            'phone' => 'nullable',
            'website' => 'nullable',
            'dateOfIncorporation' => 'nullable',
        ];
    }
}
