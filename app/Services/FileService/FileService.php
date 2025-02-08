<?php

namespace App\Services\FileService;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    public static function saveBase64Image($base64String, $folder = 'documents')
    {
        $exploded = explode(',', $base64String);
        $imageData = base64_decode($exploded[1]);

        preg_match('/^data:image\/(jpeg|png|jpg|gif|tiff);base64,/', $base64String, $matches);
        $extension = $matches[1] ?? 'png';

        $fileName = Str::uuid() . '.' . $extension;

        Storage::disk('public')->put("$folder/$fileName", $imageData);

        return "storage/$folder/$fileName";
    }
}
