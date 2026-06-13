<?php
// 配置文件

return [
    // 默认应用
    'default_app' => 'admin',
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',
    // 开启路由
    'url_route_on' => true,
    // 路由配置文件
    'route_config_file' => ['route'],
    // 是否开启路由缓存
    'route_check_cache' => false,
    // URL模式
    'url_route_must' => false,
    // 控制器层名称
    'url_controller_layer' => 'controller',
    // 操作方法前缀
    'action_suffix' => '',
    // 默认返回类型
    'default_return_type' => 'json',
    // 默认AJAX返回格式
    'default_ajax_return' => 'json',
    // 允许的跨域域名
    'cors_request_domain' => ['*'],
    // 是否开启Session
    'session_start' => true,
    // Session配置
    'session' => [
        'type' => 'file',
        'expire' => 86400,
        'prefix' => 'erp_',
        'auto_start' => true,
    ],
    // 缓存配置
    'cache' => [
        'type' => 'file',
        'path' => '../runtime/cache/',
        'expire' => 0,
        'prefix' => 'erp_',
    ],
    // 异常处理
    'exception_handle' => '',
];
