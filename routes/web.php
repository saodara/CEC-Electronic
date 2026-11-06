<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\BillController as AdminBillController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\ExpenseController as AdminExpenseController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\PurchaseOrderController as AdminPurchaseOrderController;
use App\Http\Controllers\Admin\StockAdjustmentController as AdminStockAdjustmentController;
use App\Http\Controllers\Admin\BusinessReportController as AdminBusinessReportController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DeliveryZoneController as AdminDeliveryZoneController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Customer\AccountController;
use App\Http\Controllers\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Customer\PasswordResetController as CustomerPasswordResetController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
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
Route::post('/checkout/regenerate-qr/{order}', [CheckoutController::class, 'regenerateQr'])->name('checkout.regenerate-qr');
Route::get('/checkout/payment-status/{order}', [CheckoutController::class, 'paymentStatus'])->name('checkout.payment-status');

Route::get('/login', [CustomerAuthController::class, 'login'])->name('customer.login');
Route::post('/login', [CustomerAuthController::class, 'authenticate'])
    ->name('customer.login.store')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);
Route::get('/register', [CustomerAuthController::class, 'register'])->name('customer.register');
Route::post('/register', [CustomerAuthController::class, 'store'])
    ->name('customer.register.store')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);
Route::post('/logout', [CustomerAuthController::class, 'logout'])
    ->name('customer.logout')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);

Route::get('/forgot-password', [CustomerPasswordResetController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [CustomerPasswordResetController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
Route::get('/reset-password/{token}', [CustomerPasswordResetController::class, 'edit'])->name('password.reset');
Route::post('/reset-password', [CustomerPasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.store');

Route::prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [AccountController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/receipt', [AccountController::class, 'receipt'])->name('orders.receipt');
    Route::get('/orders/{order}/receipt/view', [AccountController::class, 'viewReceipt'])->name('orders.receipt.view');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [CustomerProfileController::class, 'edit'])->name('profile');
        Route::patch('/profile', [CustomerProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/email', [CustomerProfileController::class, 'updateEmail'])->middleware('throttle:10,1')->name('email.update');
        Route::put('/profile/password', [CustomerProfileController::class, 'updatePassword'])->middleware('throttle:10,1')->name('password.update');
        Route::get('/avatar', [CustomerProfileController::class, 'showAvatar'])->name('avatar');
        Route::delete('/avatar', [CustomerProfileController::class, 'destroyAvatar'])->name('avatar.destroy');
    });
});

Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'store'])
    ->name('admin.login.store')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);
Route::post('/admin/logout', [AdminAuthController::class, 'destroy'])
    ->name('admin.logout')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);

Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\EnsureAdminSession::class)->group(function () {
    Route::get('/', [AdminProductController::class, 'dashboard'])->name('dashboard');
    Route::resource('products', AdminProductController::class);
    Route::resource('categories', AdminCategoryController::class)->except(['show']);
    Route::resource('brands', AdminBrandController::class)->except(['show']);
    Route::get('payment-notifications', [AdminOrderController::class, 'paymentNotifications'])->name('payment-notifications');
    Route::resource('orders', AdminOrderController::class)->only(['index', 'show', 'update']);
    Route::get('orders/{order}/receipt', [AdminOrderController::class, 'receipt'])->name('orders.receipt');
    Route::post('orders/{order}/verify-payment', [AdminOrderController::class, 'verifyPayment'])->name('orders.verify-payment');
    Route::get('customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{phone}', [AdminCustomerController::class, 'show'])->name('customers.show');
    Route::resource('suppliers', AdminSupplierController::class)->except(['show']);
    Route::resource('delivery-zones', AdminDeliveryZoneController::class)->except(['show']);
    Route::resource('purchase-orders', AdminPurchaseOrderController::class);
    Route::post('purchase-orders/{purchase_order}/order', [AdminPurchaseOrderController::class, 'markOrdered'])->name('purchase-orders.order');
    Route::post('purchase-orders/{purchase_order}/receive', [AdminPurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::post('purchase-orders/{purchase_order}/cancel', [AdminPurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::post('purchase-orders/{purchase_order}/bill', [AdminPurchaseOrderController::class, 'createBill'])->name('purchase-orders.bill');
    Route::resource('bills', AdminBillController::class)->only(['index', 'show', 'update', 'destroy']);
    Route::post('bills/{bill}/pay', [AdminBillController::class, 'markPaid'])->name('bills.pay');
    Route::post('bills/{bill}/unpay', [AdminBillController::class, 'markUnpaid'])->name('bills.unpay');
    Route::get('invoices', [AdminInvoiceController::class, 'index'])->name('invoices.index');
    Route::post('orders/{order}/invoice', [AdminInvoiceController::class, 'store'])->name('invoices.store');
    Route::get('invoices/{invoice}', [AdminInvoiceController::class, 'show'])->name('invoices.show');
    Route::get('invoices/{invoice}/pdf', [AdminInvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::resource('stock-adjustments', AdminStockAdjustmentController::class)->only(['index', 'create', 'store']);
    Route::resource('expenses', AdminExpenseController::class)->except(['show']);
    Route::get('reports/profit-loss', [AdminBusinessReportController::class, 'profitLoss'])->name('reports.profit-loss');
    Route::get('reports/products', [AdminBusinessReportController::class, 'products'])->name('reports.products');
    Route::get('reports/customers', [AdminBusinessReportController::class, 'customers'])->name('reports.customers');
    Route::get('reports/suppliers', [AdminBusinessReportController::class, 'suppliers'])->name('reports.suppliers');
    Route::get('reports/{period}', [AdminReportController::class, 'orders'])->whereIn('period', ['day', 'week', 'month', 'year'])->name('reports.orders');
    Route::get('reports/{period}/pdf', [AdminReportController::class, 'ordersPdf'])->whereIn('period', ['day', 'week', 'month', 'year'])->name('reports.orders.pdf');
});
