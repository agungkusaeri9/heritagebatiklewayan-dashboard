<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VariantTypeController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('users', UserController::class);
    Route::resource('customers', CustomerController::class)->except(['create', 'store']);
    Route::resource('payment-methods', PaymentMethodController::class)->except(['create', 'show', 'edit']);
    
    Route::post('couriers/{courier}/toggle', [App\Http\Controllers\CourierController::class, 'toggle'])->name('couriers.toggle');
    Route::resource('couriers', App\Http\Controllers\CourierController::class)->except(['create', 'show', 'edit']);
    Route::resource('categories', CategoryController::class);
    Route::resource('variant-types', VariantTypeController::class)->except(['show']);
    Route::get('variant-types/{variant_type}/values', [VariantTypeController::class, 'values'])->name('variant-types.values');
    Route::post('variant-types/{variant_type}/values', [VariantTypeController::class, 'syncValues'])->name('variant-types.values.sync');
    Route::get('products/template', [ProductController::class, 'downloadTemplate'])->name('products.template');
    Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
    Route::resource('products', ProductController::class);
    Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');
    
    // Locations UI
    Route::get('locations', [\App\Http\Controllers\LocationController::class, 'provinces'])->name('locations.provinces');
    Route::get('locations/provinces/{province}/cities', [\App\Http\Controllers\LocationController::class, 'cities'])->name('locations.cities');
    Route::get('locations/cities/{city}/subdistricts', [\App\Http\Controllers\LocationController::class, 'subdistricts'])->name('locations.subdistricts');

    // Locations API
    Route::get('api/locations/cities', [\App\Http\Controllers\LocationController::class, 'getCities'])->name('api.locations.cities');
    Route::get('api/locations/subdistricts', [\App\Http\Controllers\LocationController::class, 'getSubdistricts'])->name('api.locations.subdistricts');
    
    // Store Settings
    Route::get('store-settings', [\App\Http\Controllers\StoreSettingController::class, 'index'])->name('store-settings.index');
    Route::post('store-settings', [\App\Http\Controllers\StoreSettingController::class, 'update'])->name('store-settings.update');

    Route::middleware(['role:super admin'])->group(function () {
        Route::resource('roles', RoleController::class);
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
    });
});

require __DIR__.'/settings.php';
