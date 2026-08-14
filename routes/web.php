<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'citizen.app')->name('citizen.app');

Route::view('/ui-showcase', 'ui-showcase')->name('ui-showcase');

Route::prefix('admin')->name('admin.')->group(function (): void {
    // F01 Admin auth endpoints are added in the internal login task.

    Route::get('/', DashboardController::class)->name('dashboard');
});

Route::view('/{path}', 'citizen.app')
    ->where('path', '^(?!admin(?:/|$)|api(?:/|$)).*$')
    ->name('citizen.fallback');
