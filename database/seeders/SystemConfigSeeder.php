<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemConfig;

class SystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            // 常规设置
            [
                'group' => 'general',
                'key' => 'system_name',
                'value' => 'SmartIdle ERP',
                'type' => 'text',
                'label' => 'System Name',
                'description' => 'System display name in header and login page',
                'sort' => 1,
                'status' => 1,
            ],
            [
                'group' => 'general',
                'key' => 'system_logo',
                'value' => '/Logo.png',
                'type' => 'image',
                'label' => 'System Logo',
                'description' => 'Logo file path (relative to public folder)',
                'sort' => 2,
                'status' => 1,
            ],
            [
                'group' => 'general',
                'key' => 'login_logo',
                'value' => '/Logo.png',
                'type' => 'image',
                'label' => 'Login Page Logo',
                'description' => 'Logo shown on login page',
                'sort' => 3,
                'status' => 1,
            ],
            [
                'group' => 'general',
                'key' => 'company_name',
                'value' => 'SmartIdle Tech Inc.',
                'type' => 'text',
                'label' => 'Company Name',
                'description' => 'Company name displayed in footer',
                'sort' => 4,
                'status' => 1,
            ],
            
            // 安全设置
            [
                'group' => 'security',
                'key' => 'captcha_enabled',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Enable Captcha',
                'description' => 'Show captcha on login page',
                'sort' => 10,
                'status' => 1,
            ],
            [
                'group' => 'security',
                'key' => 'captcha_length',
                'value' => '4',
                'type' => 'number',
                'label' => 'Captcha Length',
                'description' => 'Number of characters in captcha',
                'sort' => 11,
                'status' => 1,
            ],
            [
                'group' => 'security',
                'key' => 'max_login_attempts',
                'value' => '5',
                'type' => 'number',
                'label' => 'Max Login Attempts',
                'description' => 'Maximum failed login attempts before lockout',
                'sort' => 12,
                'status' => 1,
            ],
            [
                'group' => 'security',
                'key' => 'session_timeout',
                'value' => '120',
                'type' => 'number',
                'label' => 'Session Timeout (minutes)',
                'description' => 'Session expire time in minutes',
                'sort' => 13,
                'status' => 1,
            ],
            
            // 业务设置
            [
                'group' => 'business',
                'key' => 'default_warehouse_id',
                'value' => '1',
                'type' => 'number',
                'label' => 'Default Warehouse',
                'description' => 'Default warehouse for stock operations',
                'sort' => 20,
                'status' => 1,
            ],
            [
                'group' => 'business',
                'key' => 'tax_rate',
                'value' => '10',
                'type' => 'number',
                'label' => 'Default Tax Rate (%)',
                'description' => 'Default tax rate for orders',
                'sort' => 21,
                'status' => 1,
            ],
            [
                'group' => 'business',
                'key' => 'low_stock_threshold',
                'value' => '10',
                'type' => 'number',
                'label' => 'Low Stock Threshold',
                'description' => 'Default low stock warning quantity',
                'sort' => 22,
                'status' => 1,
            ],
        ];

        foreach ($configs as $config) {
            SystemConfig::updateOrCreate(
                ['key' => $config['key']],
                $config
            );
        }
    }
}
