<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\middleware\Auth;
use app\model\Employee;
use think\captcha\facade\Captcha;

/**
 * 登录控制器
 */
class Login extends BaseController
{
    /**
     * 登录页面
     */
    public function index()
    {
        return view('login/index');
    }

    /**
     * 验证码
     */
    public function captcha()
    {
        return Captcha::create();
    }

    /**
     * 登录验证
     */
    public function check()
    {
        $email = $this->post('email', '');
        $password = $this->post('password', '');
        $captcha = $this->post('captcha', '');

        // 验证验证码
        if (!captcha_check($captcha)) {
            return $this->error('验证码错误');
        }

        if (empty($email) || empty($password)) {
            return $this->error('请输入邮箱和密码');
        }

        // 查找员工
        $employee = Employee::where('email', $email)->find();
        if (!$employee) {
            return $this->error('账号或密码错误');
        }

        // 检查状态
        if ($employee->status != 1) {
            return $this->error('账号已被禁用');
        }

        // 验证密码
        if (!$employee->checkPassword($password)) {
            return $this->error('账号或密码错误');
        }

        // 生成Token
        $token = Auth::generateToken($employee);

        // 更新登录信息
        $employee->last_login_time = time();
        $employee->last_login_ip = $this->request->ip();
        $employee->save();

        return $this->success([
            'token' => $token,
            'employee' => [
                'id' => $employee->id,
                'code' => $employee->code,
                'name' => $employee->name,
                'email' => $employee->email,
                'role_id' => $employee->role_id,
            ],
        ]);
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        $token = $this->request->header('Authorization', '');
        if ($token) {
            Auth::clearToken($token);
        }

        return $this->success(null, '退出成功');
    }
}
