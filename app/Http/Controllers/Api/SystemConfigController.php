<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SystemConfigController extends Controller
{
    /**
     * 获取所有配置
     */
    public function index()
    {
        $configs = SystemConfig::where('status', 1)
            ->orderBy('group')
            ->orderBy('sort')
            ->get();
        
        $grouped = $configs->groupBy('group');
        
        return $this->success([
            'configs' => $configs,
            'grouped' => $grouped,
        ]);
    }

    /**
     * 获取特定分组的配置
     */
    public function getByGroup($group)
    {
        $configs = SystemConfig::getByGroup($group);
        return $this->success($configs);
    }

    /**
     * 更新配置
     */
    public function update(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:64',
            'value' => 'nullable|string|max:500',
        ]);

        $config = SystemConfig::where('key', $request->input('key'))->first();
        
        if (!$config) {
            return $this->error('Config not found', 404);
        }

        $config->update(['value' => $request->input('value')]);
        
        return $this->success($config, 'Config updated successfully');
    }

    /**
     * 批量更新配置
     */
    public function batchUpdate(Request $request)
    {
        $request->validate([
            'configs' => 'required|array',
        ]);

        $configs = $request->input('configs');
        
        foreach ($configs as $key => $value) {
            SystemConfig::where('key', $key)->update(['value' => $value]);
        }
        
        return $this->success(null, 'Configs updated successfully');
    }

    /**
     * 上传Logo
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpeg,jpg|max:2048',
        ]);

        $file = $request->file('logo');
        $type = $request->input('type', 'logo');
        
        // 生成唯一文件名
        $filename = $type . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        
        // 保存到 public/uploads/logos 目录
        $path = $file->move(public_path('uploads/logos'), $filename);
        
        $relativePath = '/uploads/logos/' . $filename;
        
        return $this->success([
            'path' => $relativePath,
            'filename' => $filename,
        ], 'Logo uploaded successfully');
    }

    /**
     * 获取公开展示的配置（登录页等）
     */
    public function publicConfig()
    {
        $publicConfigs = [
            'system_name' => SystemConfig::getValue('system_name', 'SmartIdle ERP'),
            'login_logo' => SystemConfig::getValue('login_logo', '/Logo.png'),
            'system_logo' => SystemConfig::getValue('system_logo', '/Logo.png'),
            'company_name' => SystemConfig::getValue('company_name', 'SmartIdle Tech Inc.'),
            'captcha_enabled' => SystemConfig::getValue('captcha_enabled', '1') === '1',
        ];
        
        return $this->success($publicConfigs);
    }
}
