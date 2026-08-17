<?php

use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'citizen.app')->name('citizen.app');

Route::view('/ui-showcase', 'ui-showcase')->name('ui-showcase');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'internal'])->group(function (): void {
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');
    });
});

Route::view('/{path}', 'citizen.app')
    ->where('path', '^(?!admin(?:/|$)|api(?:/|$)).*$')
    ->name('citizen.fallback');
