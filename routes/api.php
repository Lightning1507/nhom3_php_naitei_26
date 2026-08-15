<?php

use App\Http\Controllers\Api\V1\Auth\CitizenAuthController;
use App\Http\Controllers\Api\V1\Auth\GoogleCitizenAuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('/health', HealthController::class)->name('health');

    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('/register', [CitizenAuthController::class, 'register'])->name('register');
        Route::post('/login', [CitizenAuthController::class, 'login'])->name('login');

        Route::prefix('google')
            ->middleware('web')
            ->name('google.')
            ->group(function (): void {
                Route::get('/redirect', [GoogleCitizenAuthController::class, 'redirect'])->name('redirect');
                Route::get('/callback', [GoogleCitizenAuthController::class, 'callback'])->name('callback');
                Route::get('/pending', [GoogleCitizenAuthController::class, 'pending'])->name('pending');
                Route::post('/complete', [GoogleCitizenAuthController::class, 'complete'])->name('complete');
            });

        Route::middleware(['auth:sanctum', 'citizen'])->group(function (): void {
            Route::post('/logout', [CitizenAuthController::class, 'logout'])->name('logout');
        });
    });

    Route::middleware(['auth:sanctum', 'citizen'])->group(function (): void {
        Route::get('/me', [ProfileController::class, 'show'])->name('profile.show');
        Route::patch('/me', [ProfileController::class, 'update'])->name('profile.update');
    });
});
