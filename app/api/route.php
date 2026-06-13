<?php

// API路由
Route::group('api', function () {
    // 公开接口
    Route::get('product/list', 'api.Product/list');
    Route::get('product/detail', 'api.Product/detail');
    Route::get('product/categories', 'api.Product/categories');
    Route::get('product/skuStock', 'api.Product/skuStock');

    // 需要认证的接口可以在这里添加
    // Route::post('order/create', 'api.Order/create')->middleware(\app\api\middleware\Auth::class);
});
