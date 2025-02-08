<?php

use App\Http\Controllers\API\KycScreenerController;
use App\Http\Middleware\VerifyApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/screen', [KycScreenerController::class, 'screen'])->middleware(VerifyApiKey::class);
