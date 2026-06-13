<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SystemConfig;
use App\Models\Captcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * 用户登录
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // 检查是否启用了验证码
        $captchaEnabled = SystemConfig::getValue('captcha_enabled', '1') === '1';
        
        if ($captchaEnabled) {
            $captchaKey = $request->input('captcha_key');
            $captchaCode = $request->input('captcha_code');
            
            if (empty($captchaKey) || empty($captchaCode)) {
                return $this->error('Please enter captcha code', 400);
            }
            
            $result = Captcha::verify($captchaKey, $captchaCode);
            
            if (!$result['valid']) {
                return $this->error($result['error'], 400);
            }
        }

        $employee = Employee::where('email', $request->email)
            ->where('status', 1)
            ->first();

        if (!$employee || !Hash::check($request->password, $employee->password)) {
            return $this->error('Invalid email or password', 401);
        }

        // 创建 Sanctum Token
        $token = $employee->createToken('auth_token')->plainTextToken;

        return $this->success([
            'employee' => [
                'id' => $employee->id,
                'code' => $employee->code,
                'name' => $employee->name,
                'email' => $employee->email,
                'position' => $employee->position,
                'department' => $employee->department?->name,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful');
    }

    /**
     * 用户注册
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:128',
            'email' => 'required|email|unique:employees,email',
            'password' => 'required|string|min:6|confirmed',
            'code' => 'required|string|unique:employees,code',
        ]);

        $employee = Employee::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'code' => $request->code,
            'status' => 1,
        ]);

        $token = $employee->createToken('auth_token')->plainTextToken;

        return $this->success([
            'employee' => [
                'id' => $employee->id,
                'code' => $employee->code,
                'name' => $employee->name,
                'email' => $employee->email,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'Registration successful', 201);
    }

    /**
     * 获取当前用户信息
     */
    public function user(Request $request)
    {
        $employee = $request->user();

        return $this->success([
            'id' => $employee->id,
            'code' => $employee->code,
            'name' => $employee->name,
            'email' => $employee->email,
            'position' => $employee->position,
            'department' => $employee->department?->name,
            'role' => $employee->role?->name,
            'permissions' => $employee->role?->permissions->pluck('code'),
        ]);
    }

    /**
     * 用户登出
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logout successful');
    }
}
