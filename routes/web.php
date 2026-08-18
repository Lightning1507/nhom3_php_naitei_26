<?php

use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Departments\DepartmentController;
use App\Http\Controllers\Admin\ServiceCategories\ServiceCategoryController;
use App\Http\Controllers\Api\V1\Auth\GoogleCitizenAuthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'citizen.app')->name('citizen.app');

Route::view('/ui-showcase', 'ui-showcase')->name('ui-showcase');

Route::prefix('api/v1/auth/google')
    ->name('api.v1.auth.google.')
    ->group(function (): void {
        Route::get('/redirect', [GoogleCitizenAuthController::class, 'redirect'])->name('redirect');
        Route::get('/callback', [GoogleCitizenAuthController::class, 'callback'])->name('callback');
        Route::get('/pending', [GoogleCitizenAuthController::class, 'pending'])->name('pending');
        Route::post('/complete', [GoogleCitizenAuthController::class, 'complete'])->name('complete');
    });

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'internal'])->group(function (): void {
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::resource('departments', DepartmentController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::resource('service-categories', ServiceCategoryController::class)
            ->except(['show']);
    });
});

Route::view('/{path}', 'citizen.app')
    ->where('path', '^(?!admin(?:/|$)|api(?:/|$)).*$')
    ->name('citizen.fallback');
