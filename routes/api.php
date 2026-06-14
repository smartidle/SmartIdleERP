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
use App\Http\Controllers\Api\PurchaseReturnController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\SystemConfigController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InventoryCheckController;
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\ReturnController;
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\BomController;
use App\Http\Controllers\Api\WorkOrderController;
use App\Http\Controllers\Api\StockTransferController;
use App\Http\Controllers\Api\PurchaseInquiryController;
use App\Http\Controllers\Api\OperationLogController;
use App\Http\Controllers\Api\CostRecordController;
use App\Http\Controllers\Api\ReconcileController;
use App\Http\Controllers\Api\PrintTemplateController;

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
    
    // Protected routes (require authentication + operation logging)
    Route::middleware(['auth:sanctum', 'oplog'])->group(function () {
        
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

        // SKU Management (Global)
        Route::get('skus', [ProductController::class, 'allSkus']);
        Route::put('skus/{sku}', [ProductController::class, 'updateSku']);
        
        // Inventory
        Route::get('inventory', [InventoryController::class, 'index']);
        Route::get('inventory/warning', [InventoryController::class, 'warning']);
        Route::get('inventory/stock', [InventoryController::class, 'stock']);
        Route::post('inventory/adjust', [InventoryController::class, 'adjust']);

        // Inventory Check (盘点)
        Route::get('inventory-checks', [InventoryCheckController::class, 'index']);
        Route::post('inventory-checks', [InventoryCheckController::class, 'store']);
        Route::get('inventory-checks/{check}', [InventoryCheckController::class, 'show']);
        Route::put('inventory-checks/{check}/items', [InventoryCheckController::class, 'updateItems']);
        Route::post('inventory-checks/{check}/approve', [InventoryCheckController::class, 'approve']);
        
        // Sales Orders
        Route::apiResource('sales-orders', SalesOrderController::class);
        Route::post('sales-orders/{id}/approve', [SalesOrderController::class, 'approve']);
        Route::post('sales-orders/{id}/cancel', [SalesOrderController::class, 'cancel']);

        // Sales Quotations
        Route::get('quotations', [QuotationController::class, 'index']);
        Route::get('quotations/statistics', [QuotationController::class, 'statistics']);
        Route::post('quotations', [QuotationController::class, 'store']);
        Route::get('quotations/{quotation}', [QuotationController::class, 'show']);
        Route::put('quotations/{quotation}', [QuotationController::class, 'update']);
        Route::post('quotations/{quotation}/send', [QuotationController::class, 'send']);
        Route::post('quotations/{quotation}/accept', [QuotationController::class, 'accept']);
        Route::post('quotations/{quotation}/reject', [QuotationController::class, 'reject']);
        Route::post('quotations/{quotation}/convert', [QuotationController::class, 'convertToOrder']);

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

        // Purchase Return (QC - 质检退货)
        Route::get('purchase-returns', [PurchaseReturnController::class, 'index']);
        Route::post('purchase-returns', [PurchaseReturnController::class, 'store']);
        Route::get('purchase-returns/{return}', [PurchaseReturnController::class, 'show']);
        Route::post('purchase-returns/{return}/approve', [PurchaseReturnController::class, 'approve']);
        Route::post('purchase-returns/{return}/receive', [PurchaseReturnController::class, 'receive']);
        
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

        // Invoices
        Route::get('invoices', [InvoiceController::class, 'index']);
        Route::post('invoices', [InvoiceController::class, 'store']);
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue']);
        Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void']);
        Route::post('invoices/{invoice}/match', [InvoiceController::class, 'addMatch']);
        Route::get('invoices/statistics', [InvoiceController::class, 'statistics']);

        // Employee Management
        Route::get('employees', [EmployeeController::class, 'index']);
        Route::post('employees', [EmployeeController::class, 'store'])
            ->middleware('permission:employee.create');
        Route::put('employees/{employee}', [EmployeeController::class, 'update'])
            ->middleware('permission:employee.edit');
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])
            ->middleware('permission:employee.delete');
        Route::post('employees/{employee}/change-password', [EmployeeController::class, 'changePassword']);
        Route::get('departments', [EmployeeController::class, 'departments']);
        Route::post('departments', [EmployeeController::class, 'createDepartment'])
            ->middleware('permission:department.manage');
        Route::get('roles', [EmployeeController::class, 'roles']);
        Route::post('roles', [EmployeeController::class, 'createRole'])
            ->middleware('permission:role.manage');
        Route::post('roles/{role}/permissions', [EmployeeController::class, 'assignPermissions'])
            ->middleware('permission:role.manage');
        
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
        Route::put('system-config', [SystemConfigController::class, 'update'])
            ->middleware('permission:system.config');
        Route::post('system-config/batch', [SystemConfigController::class, 'batchUpdate'])
            ->middleware('permission:system.config');
        Route::post('system-config/upload-logo', [SystemConfigController::class, 'uploadLogo'])
            ->middleware('permission:system.config');

        // Warehouses (仓库管理)
        Route::get('warehouses', [WarehouseController::class, 'index']);
        Route::post('warehouses', [WarehouseController::class, 'store']);
        Route::get('warehouses/all', [WarehouseController::class, 'all']);
        Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show']);
        Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update']);
        Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy']);

        // Locations (库位管理)
        Route::get('locations', [LocationController::class, 'index']);
        Route::post('locations', [LocationController::class, 'store']);
        Route::get('locations/warehouse/{warehouseId}', [LocationController::class, 'byWarehouse']);
        Route::get('locations/{location}', [LocationController::class, 'show']);
        Route::put('locations/{location}', [LocationController::class, 'update']);
        Route::delete('locations/{location}', [LocationController::class, 'destroy']);

        // BOM (物料清单)
        Route::get('boms', [BomController::class, 'index']);
        Route::post('boms', [BomController::class, 'store']);
        Route::get('boms/{bom}', [BomController::class, 'show']);
        Route::put('boms/{bom}', [BomController::class, 'update']);
        Route::put('boms/{bom}/items', [BomController::class, 'updateItems']);
        Route::delete('boms/{bom}', [BomController::class, 'destroy']);

        // Work Orders (生产工单)
        Route::get('work-orders', [WorkOrderController::class, 'index']);
        Route::get('work-orders/statistics', [WorkOrderController::class, 'statistics']);
        Route::post('work-orders', [WorkOrderController::class, 'store']);
        Route::get('work-orders/{workOrder}', [WorkOrderController::class, 'show']);
        Route::put('work-orders/{workOrder}', [WorkOrderController::class, 'update']);
        Route::post('work-orders/{workOrder}/approve', [WorkOrderController::class, 'approve']);
        Route::post('work-orders/{workOrder}/report', [WorkOrderController::class, 'report']);
        Route::post('work-orders/{workOrder}/close', [WorkOrderController::class, 'close']);
        Route::post('work-orders/{workOrder}/cancel', [WorkOrderController::class, 'cancel']);

        // Stock Transfers (库存调拨)
        Route::get('stock-transfers', [StockTransferController::class, 'index']);
        Route::post('stock-transfers', [StockTransferController::class, 'store']);
        Route::get('stock-transfers/{stockTransfer}', [StockTransferController::class, 'show']);
        Route::post('stock-transfers/{stockTransfer}/ship', [StockTransferController::class, 'ship']);
        Route::post('stock-transfers/{stockTransfer}/complete', [StockTransferController::class, 'complete']);
        Route::post('stock-transfers/{stockTransfer}/cancel', [StockTransferController::class, 'cancel']);
        // Inventory Freezes (库存冻结)
        Route::get('inventory-freezes', [StockTransferController::class, 'freezes']);
        Route::post('inventory-freezes/{freeze}/unfreeze', [StockTransferController::class, 'unfreeze']);

        // Purchase Inquiries (采购询价)
        Route::get('purchase-inquiries', [PurchaseInquiryController::class, 'index']);
        Route::post('purchase-inquiries', [PurchaseInquiryController::class, 'store']);
        Route::get('purchase-inquiries/{purchaseInquiry}', [PurchaseInquiryController::class, 'show']);
        Route::post('purchase-inquiries/{purchaseInquiry}/quotes', [PurchaseInquiryController::class, 'addQuote']);
        Route::post('purchase-inquiry-quotes/{quote}/select', [PurchaseInquiryController::class, 'selectQuote']);
        Route::post('purchase-inquiries/{purchaseInquiry}/cancel', [PurchaseInquiryController::class, 'cancel']);

        // Operation Logs (操作日志)
        Route::get('operation-logs', [OperationLogController::class, 'index']);
        Route::get('operation-logs/{operationLog}', [OperationLogController::class, 'show']);

        // Cost Records (成本记录)
        Route::get('cost-records', [CostRecordController::class, 'index']);
        Route::get('cost-records/statistics', [CostRecordController::class, 'statistics']);

        // Reconciles (财务核销对账)
        Route::get('reconciles', [ReconcileController::class, 'index']);
        Route::post('reconciles', [ReconcileController::class, 'store']);

        // Print Templates (打印模板)
        Route::get('print-templates', [PrintTemplateController::class, 'index']);
        Route::post('print-templates', [PrintTemplateController::class, 'store']);
        Route::get('print-templates/by-type/{type}', [PrintTemplateController::class, 'byType']);
        Route::get('print-templates/{printTemplate}', [PrintTemplateController::class, 'show']);
        Route::put('print-templates/{printTemplate}', [PrintTemplateController::class, 'update']);
        Route::delete('print-templates/{printTemplate}', [PrintTemplateController::class, 'destroy']);
    });
});
