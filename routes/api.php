<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Syscom\SyscomCategoriesController;
use App\Http\Controllers\Syscom\SyscomProductsController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:syscom')->prefix('syscom')->name('api.syscom.')->group(function () {
    Route::get('categories', [SyscomCategoriesController::class, 'index'])->name('categories.index');
    Route::get('categories/tree', [SyscomProductsController::class, 'categoryTree'])->name('categories.tree');
    Route::get('categories/{id}/path', [SyscomProductsController::class, 'categoryPath'])->name('categories.path');
    Route::get('categories/{id}', [SyscomCategoriesController::class, 'show'])->name('categories.show');
    Route::get('products', [SyscomProductsController::class, 'index'])->name('products.index');
    Route::get('products/brands', [SyscomProductsController::class, 'brands'])->name('products.brands');
    Route::get('products/{id}', [SyscomProductsController::class, 'show'])->name('products.show');
});

Route::middleware([
    SubstituteBindings::class,
    AddQueuedCookiesToResponse::class,
    'cart.session',
])->prefix('cart')->name('api.cart.')->group(function () {
    Route::get('/', [CartController::class, 'show'])->name('show');
    Route::post('items', [CartController::class, 'store'])->name('items.store');
    Route::patch('items/{itemId}', [CartController::class, 'update'])->name('items.update');
    Route::delete('items/{itemId}', [CartController::class, 'destroy'])->name('items.destroy');
    Route::delete('/', [CartController::class, 'clear'])->name('clear');
    Route::post('sync', [CartController::class, 'sync'])->name('sync');
});

Route::middleware([
    SubstituteBindings::class,
    AddQueuedCookiesToResponse::class,
    'cart.session',
])->prefix('checkout')->name('api.checkout.')->group(function () {
    Route::get('preview', [CheckoutController::class, 'preview'])->name('preview');
    Route::post('confirm', [CheckoutController::class, 'confirm'])->name('confirm');
    Route::get('orders/{number}', [CheckoutController::class, 'show'])->name('orders.show');
});

Route::post('webhooks/payment', [PaymentWebhookController::class, 'handle'])->name('api.webhooks.payment');
