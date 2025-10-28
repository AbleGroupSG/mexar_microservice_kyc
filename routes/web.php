<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard\Home;
use App\Livewire\Dashboard\ApiKeys;
use App\Livewire\Dashboard\KycProfiles;

Route::get('/', [HomeController::class, 'index']);

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// Dashboard routes
Route::middleware('auth')->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', Home::class)->name('home');
    Route::get('/api-keys', ApiKeys::class)->name('api-keys');
    Route::get('/kyc-profiles', KycProfiles::class)->name('kyc-profiles');

    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');
});

require __DIR__ . '/webhooks.php';
