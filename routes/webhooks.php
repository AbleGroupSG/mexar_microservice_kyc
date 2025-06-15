<?php

use App\Http\Webhooks\RegtankWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/kyc', [RegtankWebhookController::class, 'kyc']);
Route::post('/liveness', [RegtankWebhookController::class, 'liveness']);
Route::post('/djkyb', [RegtankWebhookController::class, 'djkyb']);
