<?php
// +----------------------------------------------------------------------
// | 数据库连接测试
// +----------------------------------------------------------------------

echo "<h1>SmartIdle ERP - Database Test</h1>";

try {
    // 测试SQLite连接
    $dbPath = __DIR__ . '/../runtime/erp.db';
    
    // 确保runtime目录存在
    if (!is_dir(dirname($dbPath))) {
        mkdir(dirname($dbPath), 0777, true);
    }
    
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2 style='color:green;'>✓ SQLite Connection: OK</h2>";
    echo "<p>Database Path: $dbPath</p>";
    
    // 创建测试表
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        create_time INTEGER
    )");
    
    // 插入测试数据
    $pdo->exec("INSERT INTO test_table (name, create_time) VALUES ('Test', " . time() . ")");
    
    // 查询数据
    $stmt = $pdo->query("SELECT * FROM test_table");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Test Data:</h3>";
    echo "<pre>";
    print_r($rows);
    echo "</pre>";
    
    echo "<h2 style='color:green;'>✓ Database Test: PASSED</h2>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red;'>✗ Database Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "<h2>Quick Links</h2>";
echo "<ul>";
echo "<li><a href='/'>Home</a></li>";
echo "<li><a href='/admin/index/menu'>API Menu</a></li>";
echo "<li><a href='/dbtest.php'>DB Test</a></li>";
echo "</ul>";
