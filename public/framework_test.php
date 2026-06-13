<?php
// +----------------------------------------------------------------------
// | ThinkPHP框架测试
// +----------------------------------------------------------------------

echo "<h1>SmartIdle ERP - Framework Test</h1>";

try {
    // 加载Composer自动加载
    require __DIR__ . '/../vendor/autoload.php';
    
    echo "<h2>Composer Autoload: OK</h2>";
    
    // 测试ThinkPHP核心类
    if (class_exists('think\App')) {
        echo "<h2 style='color:green;'>✓ think\\App: Found</h2>";
    } else {
        echo "<h2 style='color:red;'>✗ think\\App: Not Found</h2>";
    }
    
    // ThinkPHP 8 使用 new think\App() 不带参数
    $app = new think\App();
    
    echo "<h2 style='color:green;'>✓ App Instance: Created</h2>";
    
    // 测试数据库连接
    $dbPath = __DIR__ . '/../runtime/erp.db';
    $pdo = new PDO('sqlite:' . $dbPath);
    
    echo "<h2 style='color:green;'>✓ Database: Connected</h2>";
    
    echo "<h2>Framework Test: PASSED</h2>";
    
    // 显示路由
    echo "<h2>API Routes:</h2>";
    echo "<ul>";
    echo "<li><a href='/admin/index/menu'>GET /admin/index/menu</a> - 获取菜单</li>";
    echo "<li><a href='/admin/index/dashboard'>GET /admin/index/dashboard</a> - 获取仪表盘数据</li>";
    echo "<li><a href='/admin/index/welcome'>GET /admin/index/welcome</a> - 欢迎页</li>";
    echo "</ul>";
    
    echo "<h2>Quick Links</h2>";
    echo "<ul>";
    echo "<li><a href='/dbtest.php'>DB Test</a> - 数据库测试</li>";
    echo "<li><a href='/framework_test.php'>Framework Test</a> - 框架测试</li>";
    echo "</ul>";
    
} catch (Throwable $e) {
    echo "<h2 style='color:red;'>✗ Error:</h2>";
    echo "<p>Message: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    
    echo "<h3>Stack Trace:</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
