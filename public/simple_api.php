<?php
// +----------------------------------------------------------------------
// | 简单API测试
// +----------------------------------------------------------------------

// 加载Composer自动加载
require __DIR__ . '/../vendor/autoload.php';

// 创建应用实例
$app = new think\App();

// 设置响应头
header('Content-Type: application/json');

// 运行应用并获取响应
$response = $app->run();

// 发送响应
$response->send();
