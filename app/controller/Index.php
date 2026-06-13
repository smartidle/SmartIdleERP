<?php

namespace app\controller;

use think\Request;

/**
 * 后台首页控制器
 */
class Index
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
        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'pending_orders' => 0,
                'today_orders' => 0,
                'today_sales' => 0,
                'low_stock_count' => 0,
                'warnings' => [],
            ],
        ]);
    }
}
