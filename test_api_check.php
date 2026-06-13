<?php
// 直接测试API

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$employee = App\Models\Employee::where('email', 'admin@erp.com')->first();
$token = $employee->createToken('test-token')->plainTextToken;

// 直接使用Laravel请求
$request = Request::create('/api/v1/products', 'GET');
$request->headers->set('Authorization', 'Bearer ' . $token);
$request->headers->set('Accept', 'application/json');

$response = app()->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";
