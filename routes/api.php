<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaptchaController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\SalesOrderController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\PurchaseReceiveController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\SystemConfigController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\ReturnController;
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// API Version 1
Route::prefix('v1')->group(function () {
    
    // Public routes (no authentication required)
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('register', [AuthController::class, 'register']);
    
    // Captcha
    Route::get('captcha', [CaptchaController::class, 'generate']);
    Route::post('captcha/verify', [CaptchaController::class, 'verify']);
    
    // Public config
    Route::get('config/public', [SystemConfigController::class, 'publicConfig']);
    
    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Auth
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        
        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);
        
        // Products
        Route::apiResource('products', ProductController::class);
        Route::get('products/search', [ProductController::class, 'search']);
        Route::get('products/{id}/skus', [ProductController::class, 'skus']);
        
        // Inventory
        Route::get('inventory', [InventoryController::class, 'index']);
        Route::get('inventory/warning', [InventoryController::class, 'warning']);
        Route::get('inventory/stock', [InventoryController::class, 'stock']);
        Route::post('inventory/adjust', [InventoryController::class, 'adjust']);
        
        // Sales Orders
        Route::apiResource('sales-orders', SalesOrderController::class);
        Route::post('sales-orders/{id}/approve', [SalesOrderController::class, 'approve']);
        Route::post('sales-orders/{id}/cancel', [SalesOrderController::class, 'cancel']);
        
        // Sales Delivery
        Route::get('deliveries', [DeliveryController::class, 'index']);
        Route::post('deliveries', [DeliveryController::class, 'store']);
        Route::get('deliveries/{delivery}', [DeliveryController::class, 'show']);
        Route::post('deliveries/{delivery}/confirm', [DeliveryController::class, 'confirm']);
        
        // Sales Return
        Route::get('returns', [ReturnController::class, 'index']);
        Route::post('returns', [ReturnController::class, 'store']);
        Route::get('returns/{return}', [ReturnController::class, 'show']);
        Route::post('returns/{return}/approve', [ReturnController::class, 'approve']);
        Route::post('returns/{return}/receive', [ReturnController::class, 'receive']);
        Route::post('returns/{return}/exchange', [ReturnController::class, 'exchange']);
        
        // Customers
        Route::apiResource('customers', CustomerController::class);
        Route::get('customers/search', [CustomerController::class, 'search']);
        
        // Suppliers
        Route::apiResource('suppliers', SupplierController::class);
        Route::get('suppliers/search', [SupplierController::class, 'search']);
        
        // Purchase Orders
        Route::apiResource('purchase-orders', PurchaseController::class);
        Route::post('purchase-orders/{id}/approve', [PurchaseController::class, 'approve']);
        
        // Purchase Receive
        Route::get('purchase-receives', [PurchaseReceiveController::class, 'index']);
        Route::post('purchase-receives', [PurchaseReceiveController::class, 'store']);
        Route::get('purchase-receives/{receive}', [PurchaseReceiveController::class, 'show']);
        
        // Promotions & Coupons
        Route::get('promotions', [PromotionController::class, 'index']);
        Route::post('promotions', [PromotionController::class, 'store']);
        Route::put('promotions/{promotion}', [PromotionController::class, 'update']);
        Route::delete('promotions/{promotion}', [PromotionController::class, 'destroy']);
        Route::get('coupons', [PromotionController::class, 'coupons']);
        Route::post('coupons', [PromotionController::class, 'createCoupon']);
        Route::post('coupons/assign', [PromotionController::class, 'assignCoupon']);
        Route::post('coupons/validate', [PromotionController::class, 'validateCoupon']);
        
        // Finance
        Route::get('finance/receipts', [FinanceController::class, 'receipts']);
        Route::post('finance/receipts', [FinanceController::class, 'createReceipt']);
        Route::get('finance/payments', [FinanceController::class, 'payments']);
        Route::post('finance/payments', [FinanceController::class, 'createPayment']);
        Route::get('finance/accounts', [FinanceController::class, 'accounts']);
        Route::get('finance/statistics', [FinanceController::class, 'statistics']);
        
        // Employee Management
        Route::get('employees', [EmployeeController::class, 'index']);
        Route::post('employees', [EmployeeController::class, 'store']);
        Route::put('employees/{employee}', [EmployeeController::class, 'update']);
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy']);
        Route::post('employees/{employee}/change-password', [EmployeeController::class, 'changePassword']);
        Route::get('departments', [EmployeeController::class, 'departments']);
        Route::post('departments', [EmployeeController::class, 'createDepartment']);
        Route::get('roles', [EmployeeController::class, 'roles']);
        Route::post('roles', [EmployeeController::class, 'createRole']);
        Route::post('roles/{role}/permissions', [EmployeeController::class, 'assignPermissions']);
        
        // Approval
        Route::get('approvals/pending', [ApprovalController::class, 'pending']);
        Route::get('approvals/my-applications', [ApprovalController::class, 'myApplications']);
        Route::get('approvals/my-approvals', [ApprovalController::class, 'myApprovals']);
        Route::post('approvals/{record}/approve', [ApprovalController::class, 'approve']);
        Route::post('approvals/submit', [ApprovalController::class, 'submit']);
        Route::get('approvals/delegates', [ApprovalController::class, 'delegates']);
        Route::post('approvals/delegates', [ApprovalController::class, 'createDelegate']);
        
        // Notifications
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications', [NotificationController::class, 'store']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('notifications/{notification}', [NotificationController::class, 'show']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);

        // System Config
        Route::get('system-config', [SystemConfigController::class, 'index']);
        Route::get('system-config/group/{group}', [SystemConfigController::class, 'getByGroup']);
        Route::put('system-config', [SystemConfigController::class, 'update']);
        Route::post('system-config/batch', [SystemConfigController::class, 'batchUpdate']);
        Route::post('system-config/upload-logo', [SystemConfigController::class, 'uploadLogo']);
    });
});
