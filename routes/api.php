<?php

use App\Http\Controllers\API\OCRController;
use App\Http\Controllers\API\KYCScreenController;
use App\Http\Controllers\EntityOnboardingController;
use App\Http\Controllers\KybScreenController;
use App\Http\Middleware\VerifyApiKey;
use App\Http\Middleware\VerifyJwtMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ProviderController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function (){
    Route::post('/ocr', [OCRController::class, 'ocr'])->middleware(VerifyJwtMiddleware::class);

    Route::get('/providers', [ProviderController::class, 'listProviders']);

    Route::middleware(VerifyApiKey::class)->group(function () {
        Route::post('/screen', [KYCScreenController::class, 'screen']);
        Route::post('/e-form-kyb', [KybScreenController::class, 'kyb']);
        Route::post('/e-form-onboarding', [EntityOnboardingController::class, 'check']);
        Route::get('/status/{uuid}', [KYCScreenController::class, 'status']);
    });
});
