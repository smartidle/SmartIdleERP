<?php
// +----------------------------------------------------------------------
// | 简单测试页面
// +----------------------------------------------------------------------

require __DIR__ . '/../vendor/autoload.php';

$app = new think\App();
$response = $app->run();

echo "<h1>SmartIdle ERP - System Test</h1>";
echo "<h2>ThinkPHP Framework Status</h2>";

echo "<p>ThinkPHP Version: " . think\facade\App::version() . "</p>";
echo "<p>Database Driver: SQLite</p>";
echo "<p>Status: OK</p>";

echo "<h2>Quick Links</h2>";
echo "<ul>";
echo "<li><a href='/admin/index/menu'>API Menu (JSON)</a></li>";
echo "<li><a href='/admin/index/dashboard'>API Dashboard (JSON)</a></li>";
echo "</ul>";
