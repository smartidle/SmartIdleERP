<?php

namespace app\admin\controller;

use app\admin\BaseController;
use think\Request;

/**
 * 后台首页控制器
 */
class Index extends BaseController
{
    /**
     * 后台首页
     */
    public function index(Request $request)
    {
        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'system' => [
                    'name' => 'SmartIdle ERP',
                    'version' => '1.0.0',
                    'copyright' => '2026 SmartIdle',
                ],
            ],
        ]);
    }

    /**
     * 欢迎页
     */
    public function welcome(Request $request)
    {
        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'message' => '欢迎使用 SmartIdle ERP 系统',
                'version' => '1.0.0',
            ],
        ]);
    }

    /**
     * 获取菜单
     */
    public function menu(Request $request)
    {
        $menus = [
            [
                'id' => 1,
                'name' => 'Dashboard',
                'path' => '/dashboard',
                'icon' => 'dashboard',
                'children' => [
                    ['id' => 11, 'name' => '工作台', 'path' => '/dashboard/workbench'],
                    ['id' => 12, 'name' => '数据概览', 'path' => '/dashboard/overview'],
                ],
            ],
            [
                'id' => 2,
                'name' => '商品管理',
                'path' => '/product',
                'icon' => 'product',
                'children' => [
                    ['id' => 21, 'name' => '产品列表', 'path' => '/product/list'],
                    ['id' => 22, 'name' => '产品分类', 'path' => '/product/category'],
                    ['id' => 23, 'name' => 'SKU管理', 'path' => '/product/sku'],
                ],
            ],
            [
                'id' => 3,
                'name' => '销售管理',
                'path' => '/sales',
                'icon' => 'sales',
                'children' => [
                    ['id' => 31, 'name' => '销售订单', 'path' => '/sales/order'],
                    ['id' => 32, 'name' => '销售报价', 'path' => '/sales/quote'],
                    ['id' => 33, 'name' => '发货管理', 'path' => '/sales/delivery'],
                    ['id' => 34, 'name' => '退货管理', 'path' => '/sales/return'],
                ],
            ],
            [
                'id' => 4,
                'name' => '采购管理',
                'path' => '/purchase',
                'icon' => 'purchase',
                'children' => [
                    ['id' => 41, 'name' => '采购订单', 'path' => '/purchase/order'],
                    ['id' => 42, 'name' => '采购询价', 'path' => '/purchase/inquiry'],
                    ['id' => 43, 'name' => '采购收货', 'path' => '/purchase/receive'],
                ],
            ],
            [
                'id' => 5,
                'name' => '库存管理',
                'path' => '/inventory',
                'icon' => 'inventory',
                'children' => [
                    ['id' => 51, 'name' => '库内库存', 'path' => '/inventory/list'],
                    ['id' => 52, 'name' => '库存调拨', 'path' => '/inventory/transfer'],
                    ['id' => 53, 'name' => '库存盘点', 'path' => '/inventory/check'],
                    ['id' => 54, 'name' => '库存预警', 'path' => '/inventory/warning'],
                ],
            ],
            [
                'id' => 6,
                'name' => '客户管理',
                'path' => '/customer',
                'icon' => 'customer',
                'children' => [
                    ['id' => 61, 'name' => '客户列表', 'path' => '/customer/list'],
                    ['id' => 62, 'name' => '客户等级', 'path' => '/customer/level'],
                    ['id' => 63, 'name' => '客户价格', 'path' => '/customer/price'],
                ],
            ],
            [
                'id' => 7,
                'name' => '供应商',
                'path' => '/supplier',
                'icon' => 'supplier',
                'children' => [
                    ['id' => 71, 'name' => '供应商列表', 'path' => '/supplier/list'],
                    ['id' => 72, 'name' => '供应商报价', 'path' => '/supplier/price'],
                ],
            ],
            [
                'id' => 8,
                'name' => '财务管理',
                'path' => '/finance',
                'icon' => 'finance',
                'children' => [
                    ['id' => 81, 'name' => '收款单', 'path' => '/finance/receipt'],
                    ['id' => 82, 'name' => '付款单', 'path' => '/finance/payment'],
                    ['id' => 83, 'name' => '财务科目', 'path' => '/finance/account'],
                ],
            ],
            [
                'id' => 9,
                'name' => '系统管理',
                'path' => '/system',
                'icon' => 'system',
                'children' => [
                    ['id' => 91, 'name' => '员工管理', 'path' => '/system/employee'],
                    ['id' => 92, 'name' => '角色权限', 'path' => '/system/role'],
                    ['id' => 93, 'name' => '系统配置', 'path' => '/system/config'],
                    ['id' => 94, 'name' => '操作日志', 'path' => '/system/log'],
                ],
            ],
        ];

        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => $menus,
        ]);
    }

    /**
     * 获取仪表盘数据
     */
    public function dashboard(Request $request)
    {
        // 待处理订单数
        $pendingOrders = \think\facade\Db::name('sales_order')
            ->whereIn('status', [1, 2])
            ->count();

        // 今日订单数
        $todayOrders = \think\facade\Db::name('sales_order')
            ->whereTime('create_time', 'today')
            ->count();

        // 今日销售额
        $todaySales = \think\facade\Db::name('sales_order')
            ->whereTime('create_time', 'today')
            ->whereIn('status', [2, 3, 4, 5])
            ->sum('total_amount');

        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'pending_orders' => $pendingOrders ?? 0,
                'today_orders' => $todayOrders ?? 0,
                'today_sales' => round($todaySales ?? 0, 2),
                'low_stock_count' => 0,
                'warnings' => [],
            ],
        ]);
    }
}
