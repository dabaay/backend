<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\AccountingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\FinancialReportController;

use App\Http\Controllers\Api\MobileManagerController;


Route::post('/login', [AuthController::class, 'login']);
Route::get('/settings', [\App\Http\Controllers\Api\SettingController::class, 'index']);



// App Manager Routes
Route::middleware(['auth:sanctum', 'permission:mobile_management'])->prefix('mobile-manager')->group(function () {
    Route::get('/dashboard', [MobileManagerController::class, 'dashboard']);
    Route::get('/customers', [MobileManagerController::class, 'customers']);
    Route::put('/customers/{id}', [MobileManagerController::class, 'updateCustomer']);
    Route::post('/customers/{id}/toggle-block', [MobileManagerController::class, 'toggleBlock']);
    Route::get('/orders', [MobileManagerController::class, 'orders']);
    Route::post('/orders/{id}/status', [MobileManagerController::class, 'updateOrderStatus']);
    Route::get('/chats', [MobileManagerController::class, 'chats']);
    Route::get('/chats/{customerId}', [MobileManagerController::class, 'messages']);
    Route::post('/chats/{customerId}/send', [MobileManagerController::class, 'sendMessage']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    
    // Profile
    Route::get('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'show']);
    Route::put('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'update']);
    Route::get('/profile/history', [\App\Http\Controllers\Api\ProfileController::class, 'history']);

    // Analytics (Accounting module access)
    Route::middleware('permission:accounting')->group(function () {
        Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
        Route::get('/analytics/products', [AnalyticsController::class, 'products']);
        Route::get('/analytics/customers', [AnalyticsController::class, 'customers']);
        Route::get('/analytics/walpo', [AnalyticsController::class, 'walpo']);
        Route::get('/analytics/expenses', [AnalyticsController::class, 'expenses']);
    });

    // Products
    Route::middleware('permission:products|pos')->group(function () {
        Route::get('/products', [\App\Http\Controllers\Api\ProductController::class, 'index']);
        Route::get('/products/{product}', [\App\Http\Controllers\Api\ProductController::class, 'show']);
    });
    Route::middleware('permission:products')->group(function () {
        Route::post('/products', [\App\Http\Controllers\Api\ProductController::class, 'store'])->middleware('permission:products,record');
        Route::match(['put', 'patch'], '/products/{product}', [\App\Http\Controllers\Api\ProductController::class, 'update'])->middleware('permission:products,edit');
        Route::delete('/products/{product}', [\App\Http\Controllers\Api\ProductController::class, 'destroy'])->middleware('permission:products,delete');
    });

    // Customers
    Route::middleware('permission:customers|pos')->group(function () {
        Route::get('/customers', [\App\Http\Controllers\Api\CustomerController::class, 'index']);
        Route::get('/customers/{customer}', [\App\Http\Controllers\Api\CustomerController::class, 'show']);
    });
    Route::middleware('permission:customers')->group(function () {
        Route::post('/customers', [\App\Http\Controllers\Api\CustomerController::class, 'store'])->middleware('permission:customers,record');
        Route::match(['put', 'patch'], '/customers/{customer}', [\App\Http\Controllers\Api\CustomerController::class, 'update'])->middleware('permission:customers,edit');
        Route::delete('/customers/{customer}', [\App\Http\Controllers\Api\CustomerController::class, 'destroy'])->middleware('permission:customers,delete');
    });

    // Sales (POS)
    Route::middleware('permission:pos')->group(function () {
        Route::get('/sales', [\App\Http\Controllers\Api\SaleController::class, 'index']);
        Route::get('/sales/{sale}', [\App\Http\Controllers\Api\SaleController::class, 'show']);
        Route::post('/sales', [\App\Http\Controllers\Api\SaleController::class, 'store'])->middleware('permission:pos,record');
        Route::delete('/sales/{sale}', [\App\Http\Controllers\Api\SaleController::class, 'destroy'])->middleware('permission:pos,delete');
    });

    // Users
    Route::middleware('permission:users')->group(function () {
        Route::apiResource('users', UserController::class);
    });

    // Suppliers
    Route::middleware('permission:suppliers')->group(function () {
        Route::get('/suppliers', [SupplierController::class, 'index']);
        Route::get('/suppliers/{supplier}', [SupplierController::class, 'show']);
        Route::post('/suppliers', [SupplierController::class, 'store'])->middleware('permission:suppliers,record');
        Route::match(['put', 'patch'], '/suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('permission:suppliers,edit');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('permission:suppliers,delete');
        Route::post('/suppliers/{supplier}/upload-document', [SupplierController::class, 'uploadDocument'])->middleware('permission:suppliers,edit');
    });

    // Expenses
    Route::middleware('permission:accounting')->group(function () {
        Route::get('/expenses', [\App\Http\Controllers\Api\ExpenseController::class, 'index']);
        Route::get('/expenses/{expense}', [\App\Http\Controllers\Api\ExpenseController::class, 'show']);
        Route::post('/expenses', [\App\Http\Controllers\Api\ExpenseController::class, 'store'])->middleware('permission:accounting,record');
        Route::match(['put', 'patch'], '/expenses/{expense}', [\App\Http\Controllers\Api\ExpenseController::class, 'update'])->middleware('permission:accounting,edit');
        Route::delete('/expenses/{expense}', [\App\Http\Controllers\Api\ExpenseController::class, 'destroy'])->middleware('permission:accounting,delete');
    });

    // Purchases
    Route::middleware('permission:purchases')->group(function () {
        Route::get('/purchases', [\App\Http\Controllers\Api\PurchaseController::class, 'index']);
        Route::get('/purchases/{purchase}', [\App\Http\Controllers\Api\PurchaseController::class, 'show']);
        Route::post('/purchases', [\App\Http\Controllers\Api\PurchaseController::class, 'store'])->middleware('permission:purchases,record');
        Route::match(['put', 'patch'], '/purchases/{purchase}', [\App\Http\Controllers\Api\PurchaseController::class, 'update'])->middleware('permission:purchases,edit');
        Route::delete('/purchases/{purchase}', [\App\Http\Controllers\Api\PurchaseController::class, 'destroy'])->middleware('permission:purchases,delete');
    });
    
    // Logs
    Route::get('/logs/audit', [LogController::class, 'auditLogs']);
    Route::get('/logs/backups', [LogController::class, 'backupLogs']);
    Route::get('/logs/payments', [LogController::class, 'paymentLogs']);
    
    // Accounting
    Route::middleware('permission:accounting')->group(function () {
        Route::get('/accounting/transactions', [AccountingController::class, 'transactions']);
        Route::get('/accounting/daily-summaries', [AccountingController::class, 'dailySummaries']);
        Route::get('/accounting/debts', [AccountingController::class, 'debts']);
        Route::get('/accounting/debt-payments', [AccountingController::class, 'debtPayments']);
        Route::post('/accounting/debt-payments', [AccountingController::class, 'storeDebtPayment'])->middleware('permission:accounting,record');
    });
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    
    // Financial Reports
    Route::middleware('permission:accounting')->prefix('/reports/financial')->group(function () {
        Route::get('/income-statement', [FinancialReportController::class, 'incomeStatement']);
        Route::get('/balance-sheet', [FinancialReportController::class, 'balanceSheet']);
        Route::get('/cash-flow', [FinancialReportController::class, 'cashFlow']);
        Route::get('/equity', [FinancialReportController::class, 'ownersEquity']);
        Route::get('/notes', [FinancialReportController::class, 'notes']);
    });
    
    Route::get('/stats', [\App\Http\Controllers\Api\DashboardController::class, 'stats']);

    // Walpo (Credit Sales) routes
    Route::middleware('permission:walpo')->group(function () {
        Route::get('/walpo', [\App\Http\Controllers\Api\WalpoController::class, 'index']);
        Route::post('/walpo', [\App\Http\Controllers\Api\WalpoController::class, 'store'])->middleware('permission:walpo,record');
        Route::get('/walpo/customer/{customerId}', [\App\Http\Controllers\Api\WalpoController::class, 'getCustomerHistory']);
        Route::get('/walpo/{sale}', [\App\Http\Controllers\Api\WalpoController::class, 'show']);

    });

    // Settings
    Route::post('/settings', [\App\Http\Controllers\Api\SettingController::class, 'update'])->middleware('permission:users'); // Using 'users' permission for settings

    // Backups
    Route::middleware('permission:users')->group(function () {
        Route::get('/backups', [\App\Http\Controllers\Api\BackupController::class, 'index']);
        Route::get('/backups/download/{folder}/{filename}', [\App\Http\Controllers\Api\BackupController::class, 'download'])->name('backups.download');
        Route::post('/backups/run', [\App\Http\Controllers\Api\BackupController::class, 'runBackup']);
    });
});

// ============================================================
// MOBILE API ROUTES (for Flutter Dukaan App customers)
// ============================================================
use App\Http\Controllers\Api\Mobile\AuthController as MobileAuthController;
use App\Http\Controllers\Api\Mobile\DashboardController as MobileDashboardController;
use App\Http\Controllers\Api\Mobile\ProductController as MobileProductController;
use App\Http\Controllers\Api\Mobile\FavoriteController as MobileFavoriteController;
use App\Http\Controllers\Api\Mobile\OrderController as MobileOrderController;
use App\Http\Controllers\Api\Mobile\ProfileController as MobileProfileController;
use App\Http\Controllers\Api\Mobile\StoreInfoController as MobileStoreInfoController;
use App\Http\Controllers\Api\Mobile\ChatController as MobileChatController;

Route::prefix('mobile')->group(function () {

    // Public routes
    Route::post('/register', [MobileAuthController::class, 'register']);
    Route::post('/login',    [MobileAuthController::class, 'login']);

    // Protected routes (customer Sanctum token)
    Route::middleware('auth:customer')->group(function () {

        // Auth
        Route::post('/logout',          [MobileAuthController::class, 'logout']);
        Route::post('/change-password', [MobileAuthController::class, 'changePassword']);

        // Dashboard
        Route::get('/dashboard', [MobileDashboardController::class, 'index']);

        // Products
        Route::get('/products',               [MobileProductController::class, 'index']);
        Route::get('/products/{id}',          [MobileProductController::class, 'show']);
        Route::post('/products/{id}/rate',    [MobileProductController::class, 'rate']);
        Route::get('/products/{id}/comments', [MobileProductController::class, 'comments']);

        // Favorites
        Route::get('/favorites',              [MobileFavoriteController::class, 'index']);
        Route::post('/favorites/{productId}', [MobileFavoriteController::class, 'store']);
        Route::delete('/favorites/{productId}', [MobileFavoriteController::class, 'destroy']);

        // Orders
        Route::get('/orders',              [MobileOrderController::class, 'index']);
        Route::post('/orders',             [MobileOrderController::class, 'store']);
        Route::get('/orders/{id}',         [MobileOrderController::class, 'show']);
        Route::post('/orders/{id}/cancel', [MobileOrderController::class, 'cancel']);

        // Profile
        Route::get('/profile',  [MobileProfileController::class, 'index']);
        Route::post('/profile', [MobileProfileController::class, 'update']);

        // Store Info
        Route::get('/store-info', [MobileStoreInfoController::class, 'index']);

        // Chat
        Route::get('/chat/messages',          [MobileChatController::class, 'index']);
        Route::post('/chat/messages',         [MobileChatController::class, 'store']);
        Route::post('/chat/upload-screenshot', [MobileChatController::class, 'uploadScreenshot']);
    });
});

