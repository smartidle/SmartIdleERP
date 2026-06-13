<?php
// 创建干净的SQLite数据库
$dbPath = __DIR__ . '/database/database.sqlite';

// 如果数据库存在，删除它
if (file_exists($dbPath)) {
    unlink($dbPath);
}

// 创建新数据库
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 创建基本表
$pdo->exec("CREATE TABLE migrations (
    id INTEGER PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL
)");

echo "Database created successfully at: $dbPath\n";
echo "Size: " . filesize($dbPath) . " bytes\n";
