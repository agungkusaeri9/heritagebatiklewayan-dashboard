<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AppConfigController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\ProductVirtualTryOnController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StoreSettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VariantTypeController;
use App\Http\Controllers\VirtualTryOnModelController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('users', UserController::class);
    Route::resource('customers', CustomerController::class)->except(['create', 'store']);
    Route::resource('payment-methods', PaymentMethodController::class)->except(['create', 'show', 'edit']);

    Route::post('couriers/{courier}/toggle', [CourierController::class, 'toggle'])->name('couriers.toggle');
    Route::resource('couriers', CourierController::class)->except(['create', 'show', 'edit']);
    Route::resource('categories', CategoryController::class);
    Route::post('banners/{banner}/toggle', [BannerController::class, 'toggle'])->name('banners.toggle');
    Route::resource('banners', BannerController::class)->except(['show']);
    Route::resource('variant-types', VariantTypeController::class)->except(['show']);
    Route::get('variant-types/{variant_type}/values', [VariantTypeController::class, 'values'])->name('variant-types.values');
    Route::post('variant-types/{variant_type}/values', [VariantTypeController::class, 'syncValues'])->name('variant-types.values.sync');
    Route::get('products/template', [ProductController::class, 'downloadTemplate'])->name('products.template');
    Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
    Route::resource('products', ProductController::class);
    Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');

    // Locations UI
    Route::get('locations', [LocationController::class, 'provinces'])->name('locations.provinces');
    Route::get('locations/provinces/{province}/cities', [LocationController::class, 'cities'])->name('locations.cities');
    Route::get('locations/cities/{city}/subdistricts', [LocationController::class, 'subdistricts'])->name('locations.subdistricts');

    // Locations API
    Route::get('api/locations/cities', [LocationController::class, 'getCities'])->name('api.locations.cities');
    Route::get('api/locations/subdistricts', [LocationController::class, 'getSubdistricts'])->name('api.locations.subdistricts');

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('orders/{order}/payment', [OrderController::class, 'updatePaymentStatus'])->name('orders.update-payment');
    Route::post('orders/{order}/shipping', [OrderController::class, 'updateShipping'])->name('orders.update-shipping');

    // Store Settings
    Route::get('store-settings', [StoreSettingController::class, 'index'])->name('store-settings.index');
    Route::post('store-settings', [StoreSettingController::class, 'update'])->name('store-settings.update');

    // Privacy & Policy Settings
    Route::get('privacy-policy-settings', [PrivacyPolicyController::class, 'index'])->name('privacy-policy.index');
    Route::post('privacy-policy-settings', [PrivacyPolicyController::class, 'update'])->name('privacy-policy.update');

    // FAQs
    Route::post('faqs/{faq}/toggle', [FaqController::class, 'toggle'])->name('faqs.toggle');
    Route::resource('faqs', FaqController::class);

    // Notifications
    Route::post('notifications/{notification}/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::resource('notifications', NotificationController::class)->except(['edit', 'update']);
    Route::post('notification-settings/{notification_setting}/toggle', [NotificationSettingController::class, 'toggle'])->name('notification-settings.toggle');
    Route::resource('notification-settings', NotificationSettingController::class)->except(['create', 'show', 'edit']);

    // Product Reviews
    Route::post('reviews', [ProductReviewController::class, 'store'])->name('reviews.store');
    Route::post('reviews/{review}/toggle', [ProductReviewController::class, 'toggle'])->name('reviews.toggle');
    Route::delete('reviews/{review}', [ProductReviewController::class, 'destroy'])->name('reviews.destroy');

    // Fashn AI Virtual Try-On
    Route::post('virtual-try-on-models/generate-ai', [VirtualTryOnModelController::class, 'generateAi'])->name('virtual-try-on-models.generate-ai');
    Route::resource('virtual-try-on-models', VirtualTryOnModelController::class)->except(['create', 'show', 'edit']);
    Route::post('product-virtual-try-ons/{productVirtualTryOn}/retry', [ProductVirtualTryOnController::class, 'retry'])->name('product-virtual-try-ons.retry');
    Route::resource('product-virtual-try-ons', ProductVirtualTryOnController::class)->except(['create', 'edit']);

    // App Config & Configurations
    Route::get('configurations', [AppConfigController::class, 'index'])->name('configurations.index');
    Route::get('configurations/rajaongkir', [AppConfigController::class, 'rajaongkir'])->name('configurations.rajaongkir');
    Route::post('configurations/rajaongkir', [AppConfigController::class, 'updateRajaongkir'])->name('configurations.rajaongkir.update');
    Route::get('configurations/fashn-ai', [AppConfigController::class, 'fashnAi'])->name('configurations.fashn-ai');
    Route::post('configurations/fashn-ai', [AppConfigController::class, 'updateFashnAi'])->name('configurations.fashn-ai.update');
    Route::get('configurations/geoapify', [AppConfigController::class, 'geoapify'])->name('configurations.geoapify');
    Route::post('configurations/geoapify', [AppConfigController::class, 'updateGeoapify'])->name('configurations.geoapify.update');
    Route::resource('app-configs', AppConfigController::class)->except(['create', 'show', 'edit']);

    Route::middleware(['role:super admin'])->group(function () {
        Route::resource('roles', RoleController::class);
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
    });
});

require __DIR__.'/settings.php';
