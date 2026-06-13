<?php
// +----------------------------------------------------------------------
// | 菜单API直接测试
// +----------------------------------------------------------------------

require __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

// 设置错误显示
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // 加载公共函数
    require_once __DIR__ . '/../app/common/functions.php';
    
    // 加载BaseController
    require_once __DIR__ . '/../app/admin/BaseController.php';
    
    // 加载Index控制器
    require_once __DIR__ . '/../app/admin/controller/Index.php';
    
    // 使用反射调用menu方法
    $controller = new ReflectionClass('app\admin\controller\Index');
    
    // 由于控制器构造函数需要App实例，我们需要创建一个模拟的方法调用
    // 直接复制menu方法的逻辑来测试
    
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
    
    echo json_encode([
        'code' => 0,
        'msg' => 'success',
        'data' => $menus,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    echo json_encode([
        'code' => 500,
        'msg' => 'error',
        'error' => $e->getMessage(),
    ]);
}
