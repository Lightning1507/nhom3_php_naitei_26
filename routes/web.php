<?php

use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Departments\DepartmentCandidateController;
use App\Http\Controllers\Admin\Departments\DepartmentController;
use App\Http\Controllers\Admin\Departments\DepartmentLeaderController;
use App\Http\Controllers\Admin\Departments\DepartmentMemberController;
use App\Http\Controllers\Admin\Departments\TransferDepartmentMemberController;
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
        Route::get('/departments/manager-candidates', [DepartmentCandidateController::class, 'managerCandidates'])
            ->name('departments.manager-candidates');
        Route::patch('/departments/{department}/leader', [DepartmentLeaderController::class, 'update'])
            ->name('departments.leader.update');
        Route::get('/departments/{department}/member-candidates', [DepartmentCandidateController::class, 'memberCandidates'])
            ->name('departments.member-candidates');
        Route::post('/departments/{department}/members', [DepartmentMemberController::class, 'store'])
            ->name('departments.members.store');
        Route::get('/departments/{department}/members/{member}/transfer-targets', [DepartmentCandidateController::class, 'transferTargets'])
            ->scopeBindings()
            ->name('departments.members.transfer-targets');
        Route::post('/departments/{department}/members/{member}/transfer', TransferDepartmentMemberController::class)
            ->scopeBindings()
            ->name('departments.members.transfer');
        Route::delete('/departments/{department}/members/{member}', [DepartmentMemberController::class, 'destroy'])
            ->scopeBindings()
            ->name('departments.members.destroy');
        Route::resource('departments', DepartmentController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    });
});

Route::view('/{path}', 'citizen.app')
    ->where('path', '^(?!admin(?:/|$)|api(?:/|$)).*$')
    ->name('citizen.fallback');
