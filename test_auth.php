<?php
// 测试登录

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$credentials = ['email' => 'admin@erp.com', 'password' => 'admin123'];
$employee = App\Models\Employee::where('email', $credentials['email'])->first();

if ($employee) {
    echo "Employee found: " . $employee->name . "\n";
    echo "Password check: " . (Hash::check($credentials['password'], $employee->password) ? 'OK' : 'FAIL') . "\n";
    
    if (Hash::check($credentials['password'], $employee->password)) {
        $token = $employee->createToken('api-token')->plainTextToken;
        echo "Token created: " . substr($token, 0, 50) . "...\n";
        
        // 测试dashboard
        echo "\nTesting Dashboard API...\n";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/v1/dashboard');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        
        echo "Dashboard Response: " . $result . "\n";
    }
} else {
    echo "Employee not found!\n";
}
