<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\RefreshTokenController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductReviewController;
use App\Http\Controllers\Api\StoreSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        // Guest auth routes with throttle
        Route::middleware('throttle:6,1')->group(function () {
            Route::post('register', RegisterController::class)->name('register');
            Route::post('login', LoginController::class)->name('login');
        });

        // Authenticated auth routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', MeController::class)->name('me');
            Route::post('refresh-token', RefreshTokenController::class)->name('refresh-token');
        });
    });

    // Alias: GET /api/v1/me
    Route::middleware('auth:sanctum')->get('me', MeController::class)->name('me');

    // Products
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('slug/{slug}/reviews', [ProductReviewController::class, 'index'])->name('reviews.slug-path');
        Route::get('slug/{slug}', [ProductController::class, 'showBySlug'])->name('show-by-slug');
        Route::get('{id}', [ProductController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('{slug}/reviews', [ProductReviewController::class, 'index'])->name('reviews');
        Route::get('{slug}', [ProductController::class, 'showBySlug'])->name('show.slug');
    });

    // Payment Methods
    Route::prefix('payment-methods')->name('payment-methods.')->group(function () {
        Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
        Route::get('{id}', [PaymentMethodController::class, 'show'])->whereNumber('id')->name('show');
    });

    // FAQs
    Route::prefix('faqs')->name('faqs.')->group(function () {
        Route::get('/', [FaqController::class, 'index'])->name('index');
        Route::get('{id}', [FaqController::class, 'show'])->whereNumber('id')->name('show');
    });

    // Store Settings & Policies
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [StoreSettingController::class, 'index'])->name('index');
        Route::get('privacy-policy', [StoreSettingController::class, 'privacyPolicy'])->name('privacy-policy');
    });
});
