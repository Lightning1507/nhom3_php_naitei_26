<?php

use App\Http\Controllers\Api\V1\ApplicationController;
use App\Http\Controllers\Api\V1\Auth\CitizenAuthController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('/health', HealthController::class)->name('health');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
        Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('/register', [CitizenAuthController::class, 'register'])->name('register');
        Route::post('/login', [CitizenAuthController::class, 'login'])->name('login');
        Route::post('/logout', [CitizenAuthController::class, 'logout'])
            ->middleware(['auth:sanctum', 'citizen'])
            ->name('logout');
    });
});
