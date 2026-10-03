<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VariantTypeController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('users', UserController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('variant-types', VariantTypeController::class)->except(['show']);
    Route::get('variant-types/{variant_type}/values', [VariantTypeController::class, 'values'])->name('variant-types.values');
    Route::post('variant-types/{variant_type}/values', [VariantTypeController::class, 'syncValues'])->name('variant-types.values.sync');
    Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');

    Route::middleware(['role:super admin'])->group(function () {
        Route::resource('roles', RoleController::class);
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
    });
});

require __DIR__.'/settings.php';
