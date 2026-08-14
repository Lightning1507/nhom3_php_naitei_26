<?php

use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('/health', HealthController::class)->name('health');

    Route::prefix('auth')->name('auth.')->group(function (): void {
        // F01 Citizen auth endpoints are added in the Citizen auth task.
    });

    Route::middleware(['auth:sanctum', 'citizen'])->group(function (): void {
        // F01 current Citizen profile endpoints are added in the profile task.
    });
});
