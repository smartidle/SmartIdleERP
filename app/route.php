<?php

use think\facade\Route;

// 首页路由
Route::get('/', function() {
    return json([
        'code' => 0,
        'msg' => 'Welcome to SmartIdle ERP API',
        'version' => '1.0.0',
    ]);
});

// 后台登录
Route::post('login/check', 'Login@check');
Route::get('login/captcha', 'Login@captcha');
Route::post('login/logout', 'Login@logout');

// 后台首页
Route::get('index/menu', 'Index@menu');
Route::get('index/dashboard', 'Index@dashboard');
Route::get('index/welcome', 'Index@welcome');
Route::get('index/index', 'Index@index');

// 产品管理
Route::get('product/list', 'Product@list');
Route::get('product/detail', 'Product@detail');
Route::post('product/create', 'Product@create');
Route::post('product/update', 'Product@update');
Route::post('product/delete', 'Product@delete');

// 库存管理
Route::get('inventory/list', 'Inventory@list');
Route::get('inventory/detail', 'Inventory@detail');
Route::get('inventory/warning', 'Inventory@warning');

// 销售订单
Route::get('salesOrder/list', 'SalesOrder@list');
Route::post('salesOrder/create', 'SalesOrder@create');
