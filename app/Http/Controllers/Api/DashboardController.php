<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\PurchaseOrder;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * 获取仪表盘数据
     */
    public function index(Request $request)
    {
        $employee = $request->user();

        // 今日销售订单统计
        $todaySales = SalesOrder::whereDate('order_date', today())
            ->count();

        // 本月销售订单统计
        $monthlySales = SalesOrder::whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as amount')
            ->first();

        // 待审批订单
        $pendingOrders = SalesOrder::where('status', SalesOrder::STATUS_PENDING)->count();

        // 低库存预警
        $lowStockWarning = Inventory::whereHas('product', function ($query) {
            $query->whereColumn('min_stock', '>', 'inventories.quantity');
        })->count();

        // 今日采购订单
        $todayPurchases = PurchaseOrder::whereDate('order_date', today())->count();

        // 待收货采购
        $pendingReceives = PurchaseOrder::whereIn('status', [
            PurchaseOrder::STATUS_APPROVED,
            PurchaseOrder::STATUS_PARTIAL,
        ])->count();

        return $this->success([
            'today_sales' => $todaySales,
            'monthly_sales_count' => $monthlySales->count ?? 0,
            'monthly_sales_amount' => $monthlySales->amount ?? 0,
            'pending_orders' => $pendingOrders,
            'low_stock_warning' => $lowStockWarning,
            'today_purchases' => $todayPurchases,
            'pending_receives' => $pendingReceives,
        ]);
    }

    /**
     * 获取仪表盘统计数据
     */
    public function stats(Request $request)
    {
        $employee = $request->user();

        // 总产品数
        $totalProducts = Product::where('status', 1)->count();

        // 总客户数
        $totalCustomers = Customer::where('status', 1)->count();

        // 总供应商数
        $totalSuppliers = Supplier::where('status', 1)->count();

        // 总库存SKU数
        $totalInventorySku = Inventory::distinct('sku_id')->count('sku_id');

        // 库存总价值
        $totalInventoryValue = Inventory::selectRaw('SUM(quantity * cost_price) as total')
            ->first()->total ?? 0;

        // 今日订单金额
        $todayOrderAmount = SalesOrder::whereDate('order_date', today())
            ->sum('total_amount');

        // 本月订单金额
        $monthlyOrderAmount = SalesOrder::whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->sum('total_amount');

        // 待收款金额
        $receivableAmount = SalesOrder::where('payment_status', '!=', SalesOrder::PAY_PAID)
            ->whereNotIn('status', [SalesOrder::STATUS_CANCELLED])
            ->selectRaw('SUM(total_amount - paid_amount) as total')
            ->first()->total ?? 0;

        return $this->success([
            'total_products' => $totalProducts,
            'total_customers' => $totalCustomers,
            'total_suppliers' => $totalSuppliers,
            'total_inventory_sku' => $totalInventorySku,
            'total_inventory_value' => $totalInventoryValue,
            'today_order_amount' => $todayOrderAmount,
            'monthly_order_amount' => $monthlyOrderAmount,
            'receivable_amount' => $receivableAmount,
        ]);
    }

    /**
     * 获取仪表盘小组件数据
     */
    public function widgets(Request $request, $type = null)
    {
        $type = $type ?: $request->route('type', 'overview');

        switch ($type) {
            case 'sales':
                $data = [
                    'today_count' => SalesOrder::whereDate('order_date', today())->count(),
                    'today_amount' => SalesOrder::whereDate('order_date', today())->sum('total_amount'),
                    'monthly_count' => SalesOrder::whereMonth('order_date', now()->month)
                        ->whereYear('order_date', now()->year)->count(),
                    'monthly_amount' => SalesOrder::whereMonth('order_date', now()->month)
                        ->whereYear('order_date', now()->year)->sum('total_amount'),
                    'pending_count' => SalesOrder::where('status', SalesOrder::STATUS_PENDING)->count(),
                ];
                break;
            case 'purchase':
                $data = [
                    'today_count' => PurchaseOrder::whereDate('order_date', today())->count(),
                    'pending_count' => PurchaseOrder::whereIn('status', [
                        PurchaseOrder::STATUS_APPROVED,
                        PurchaseOrder::STATUS_PARTIAL,
                    ])->count(),
                ];
                break;
            case 'inventory':
                $data = [
                    'total_sku' => Inventory::distinct('sku_id')->count('sku_id'),
                    'total_value' => Inventory::selectRaw('SUM(quantity * cost_price) as total')
                        ->first()->total ?? 0,
                    'low_stock_count' => Inventory::whereHas('product', function ($query) {
                        $query->whereColumn('min_stock', '>', 'inventories.quantity');
                    })->count(),
                ];
                break;
            default:
                $data = [
                    'today_sales' => SalesOrder::whereDate('order_date', today())->count(),
                    'pending_orders' => SalesOrder::where('status', SalesOrder::STATUS_PENDING)->count(),
                    'today_purchases' => PurchaseOrder::whereDate('order_date', today())->count(),
                ];
        }

        return $this->success($data);
    }
}
