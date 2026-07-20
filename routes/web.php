<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DeliveryProviderController as AdminDeliveryProviderController;
use App\Http\Controllers\Admin\DeliveryZoneController as AdminDeliveryZoneController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Customer\AccountController;
use App\Http\Controllers\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\HomeController;

Route::get('/', HomeController::class)->name('shop.home');
Route::get('/search', [CatalogController::class, 'search'])->name('shop.search');
Route::get('/brands', [CatalogController::class, 'brands'])->name('shop.brands');
Route::get('/brands/{slug}', [CatalogController::class, 'brand'])->name('shop.brand');
Route::get('/category/{slug}', [CatalogController::class, 'category'])->name('shop.category');
Route::get('/product/{slug}', [CatalogController::class, 'product'])->name('shop.product');

Route::get('/cart', [CartController::class, 'index'])->name('shop.cart');
Route::post('/cart/{product}', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/payment-status/{order}', [CheckoutController::class, 'paymentStatus'])->name('checkout.payment-status');
Route::post('/webhooks/bakong', [CheckoutController::class, 'webhook'])
    ->name('bakong.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

Route::get('/login', [CustomerAuthController::class, 'login'])->name('customer.login');
Route::post('/login', [CustomerAuthController::class, 'authenticate'])->name('customer.login.store');
Route::get('/register', [CustomerAuthController::class, 'register'])->name('customer.register');
Route::post('/register', [CustomerAuthController::class, 'store'])->name('customer.register.store');
Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');

Route::prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [AccountController::class, 'show'])->name('orders.show');
});

Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'store'])->name('admin.login.store');
Route::post('/admin/logout', [AdminAuthController::class, 'destroy'])->name('admin.logout');

Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureAdminSession::class)->group(function () {
    Route::get('/', [AdminProductController::class, 'dashboard'])->name('dashboard');
    Route::resource('products', AdminProductController::class)->except(['show']);
    Route::resource('categories', AdminCategoryController::class)->except(['show']);
    Route::resource('orders', AdminOrderController::class)->only(['index', 'show', 'update']);
    Route::get('customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{phone}', [AdminCustomerController::class, 'show'])->name('customers.show');
    Route::resource('suppliers', AdminSupplierController::class)->except(['show']);
    Route::resource('delivery-zones', AdminDeliveryZoneController::class)->except(['show']);
    Route::resource('delivery-providers', AdminDeliveryProviderController::class)->except(['show']);
});
