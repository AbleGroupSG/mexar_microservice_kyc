<?php

use App\Http\Controllers\API\KycController;
use App\Http\Controllers\API\KycScreenerController;
use App\Http\Controllers\EntityOnboardingController;
use App\Http\Controllers\KybScreenController;
use App\Http\Middleware\VerifyApiKey;
use App\Http\Middleware\VerifyJwtMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/screen', [KycScreenerController::class, 'screen'])->middleware(VerifyApiKey::class);
Route::post('/e-form-kyb', [KybScreenController::class, 'kyb'])->middleware(VerifyApiKey::class);
Route::post('/e-form-onboarding', [EntityOnboardingController::class, 'check'])->middleware(VerifyApiKey::class);
Route::get('/status/{uuid}', [KycScreenerController::class, 'status'])->middleware(VerifyApiKey::class);

Route::prefix('v1')->group(function (){
    Route::post('/ocr', [KycController::class, 'ocr'])->middleware(VerifyJwtMiddleware::class);
});
