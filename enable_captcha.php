<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

App\Models\SystemConfig::where('key', 'captcha_enabled')->update(['value' => '1']);
echo "Captcha enabled successfully!\n";
