<?php

/**
 * 应用公共文件
 * 在此文件中加载公共函数库
 */

// 加载公共函数库
require_once __DIR__ . '/common/functions.php';

/**
 * 公共配置
 */
return [
    // 应用命名空间
    'app_namespace' => 'app',
    // 应用调试模式
    'app_debug' => true,
    // 应用Trace
    'app_trace' => false,
    // 视图路径
    'default_return_type' => 'json',
    // 默认语言
    'default_lang' => 'zh-cn',
    // 是否强制使用路由
    'url_route_on' => true,
    // 是否开启路由缓存
    'route_check_cache' => false,
];
