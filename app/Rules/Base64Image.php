<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Base64Image implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail("The $attribute must be a valid base64-encoded image.");
            return;
        }

        if (!preg_match('/^data:image\/(jpeg|png|jpg|gif|tiff);base64,/', $value, $matches)) {
            $fail("The $attribute must be a valid image (JPEG, PNG, JPG, GIF, TIFF).");
            return;
        }

        $exploded = explode(',', $value);
        if (!isset($exploded[1])) {
            $fail("The $attribute must be a valid base64-encoded image.");
            return;
        }

        $decoded = base64_decode($exploded[1], true);
        if ($decoded === false) {
            $fail("The $attribute contains an invalid base64 string.");
            return;
        }
    }
}
