<?php

use App\Http\Controllers\Api\V1\Auth\CitizenAuthController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('/health', HealthController::class)->name('health');

    Route::prefix('auth')->middleware('web')->name('auth.')->group(function (): void {
        Route::post('/register', [CitizenAuthController::class, 'register'])->name('register');
        Route::post('/login', [CitizenAuthController::class, 'login'])->name('login');
        Route::post('/logout', [CitizenAuthController::class, 'logout'])
            ->middleware(['auth:sanctum', 'citizen'])
            ->name('logout');
    });
});
