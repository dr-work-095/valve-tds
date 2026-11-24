<?php

use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\TenantDataTableController;
use App\Http\Controllers\AdminSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Tenant\CompanySettingsController;
use App\Http\Controllers\Tenant\MaterialController;
use App\Http\Controllers\Tenant\MaterialDataTableController;
use App\Http\Controllers\Tenant\MediaController;
use App\Http\Controllers\Tenant\ValveModelController;
use App\Http\Controllers\Tenant\ValveModelDataTableController;
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

    Route::middleware('tenant.admin')->group(function () {
        Route::get('/settings', [CompanySettingsController::class, 'edit'])->name('tenant.settings.edit');
        Route::put('/settings', [CompanySettingsController::class, 'update'])->name('tenant.settings.update');
        Route::get('/materials/data', MaterialDataTableController::class)->name('tenant.materials.data');
        Route::resource('materials', MaterialController::class)
            ->names([
                'index' => 'tenant.materials.index',
                'create' => 'tenant.materials.create',
                'store' => 'tenant.materials.store',
                'edit' => 'tenant.materials.edit',
                'update' => 'tenant.materials.update',
            ])
            ->except(['show', 'destroy']);
        Route::get('/valve-models/data', ValveModelDataTableController::class)->name('tenant.valve-models.data');
        Route::resource('valve-models', ValveModelController::class, [
            'parameters' => ['valve-models' => 'valveModel'],
        ])
            ->names([
                'index' => 'tenant.valve-models.index',
                'create' => 'tenant.valve-models.create',
                'store' => 'tenant.valve-models.store',
                'edit' => 'tenant.valve-models.edit',
                'update' => 'tenant.valve-models.update',
            ])
            ->except(['show', 'destroy']);
    });

    Route::get('/media/{category}/{path}', MediaController::class)
        ->where('category', 'logos|schematics')
        ->where('path', '.*')
        ->name('tenant.media');
});
