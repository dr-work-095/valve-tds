<?php

use App\Http\Controllers\AdminSessionController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\TenantDataTableController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TenantSessionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest:web')->group(function () {
    Route::get('/login', [TenantSessionController::class, 'create'])->name('login');
    Route::post('/login', [TenantSessionController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
});

Route::post('/logout', [TenantSessionController::class, 'destroy'])
    ->middleware('auth:web')
    ->name('logout');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminSessionController::class, 'create'])->name('login');
        Route::post('/login', [AdminSessionController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
    });

    Route::middleware(['auth:admin', 'admin.active'])->group(function () {
        Route::post('/logout', [AdminSessionController::class, 'destroy'])->name('logout');
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::get('/tenants/data', TenantDataTableController::class)->name('tenants.data');
        Route::post('/tenants/{tenant}/provisioning-retry', [TenantController::class, 'retryProvisioning'])
            ->name('tenants.provisioning.retry');
        Route::resource('tenants', TenantController::class)->except(['show', 'destroy']);
    });
});

Route::middleware(['auth:web', 'tenant.initialize'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'tenant'])->name('dashboard');
});
