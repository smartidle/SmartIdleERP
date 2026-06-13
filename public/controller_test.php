<?php
// +----------------------------------------------------------------------
// | 控制器直接测试
// +----------------------------------------------------------------------

require __DIR__ . '/../vendor/autoload.php';

// 设置错误显示
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Controller Direct Test</h1>";

try {
    // 加载控制器
    $controllerFile = __DIR__ . '/../app/admin/controller/Index.php';
    
    if (file_exists($controllerFile)) {
        echo "<h2 style='color:green;'>✓ Controller File: Found</h2>";
        
        // 检查类是否存在
        $className = 'app\\admin\\controller\\Index';
        
        if (class_exists($className)) {
            echo "<h2 style='color:green;'>✓ Controller Class: Found</h2>";
            
            // 尝试实例化
            $reflection = new ReflectionClass($className);
            echo "<p>Class Methods:</p><ul>";
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() == $className) {
                    echo "<li>" . $method->getName() . "()</li>";
                }
            }
            echo "</ul>";
        } else {
            echo "<h2 style='color:red;'>✗ Controller Class: Not Found</h2>";
        }
    } else {
        echo "<h2 style='color:red;'>✗ Controller File: Not Found</h2>";
    }
    
} catch (Throwable $e) {
    echo "<h2 style='color:red;'>✗ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
