<?php

namespace app\admin\middleware;

use app\common\exception\AuthenticationException;
use app\model\Employee;
use think\App;
use think\facade\Cache;
use think\Request;
use think\Response;

/**
 * 权限认证中间件
 */
class Auth
{
    /**
     * 不需要登录的路由
     */
    protected array $noLoginRoutes = [
        'admin/login/index',
        'admin/login/captcha',
        'admin/login/check',
    ];

    /**
     * 不需要权限验证的路由
     */
    protected array $noPermissionRoutes = [
        'admin/index/index',
        'admin/index/welcome',
        'admin/dashboard/index',
    ];

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 中间件处理
     */
    public function handle(Request $request, \Closure $next): Response
    {
        // 获取当前路由
        $route = strtolower($request->module() . '/' . $request->controller() . '/' . $request->action());

        // 检查是否需要登录
        if (!in_array($route, $this->noLoginRoutes)) {
            $this->checkLogin($request);
        }

        // 检查是否需要权限验证
        if (!in_array($route, $this->noPermissionRoutes)) {
            $this->checkPermission($request);
        }

        return $next($request);
    }

    /**
     * 检查登录状态
     */
    protected function checkLogin(Request $request): void
    {
        $token = $request->header('Authorization', '');
        if (empty($token)) {
            $token = $request->param('token', '');
        }

        if (empty($token)) {
            throw new AuthenticationException('请先登录', 401);
        }

        // 验证Token
        $cacheKey = 'user_token:' . md5($token);
        $userData = Cache::get($cacheKey);

        if (empty($userData)) {
            throw new AuthenticationException('登录已过期，请重新登录', 401);
        }

        // 将用户信息注入到请求中
        $request->userData = $userData;
        $request->employeeId = $userData['id'] ?? 0;
    }

    /**
     * 检查权限
     */
    protected function checkPermission(Request $request): void
    {
        // 超级管理员拥有所有权限
        $userData = $request->userData ?? [];
        if (!empty($userData['is_super'])) {
            return;
        }

        $route = strtolower($request->module() . '/' . $request->controller() . '/' . $request->action());

        // 获取用户的权限列表
        $permissions = $userData['permissions'] ?? [];
        if (!in_array($route, $permissions) && !in_array('*', $permissions)) {
            throw new AuthenticationException('没有权限访问', 403);
        }
    }

    /**
     * 验证Token
     */
    public static function verifyToken(string $token): ?array
    {
        $cacheKey = 'user_token:' . md5($token);
        return Cache::get($cacheKey);
    }

    /**
     * 生成Token
     */
    public static function generateToken(Employee $employee): string
    {
        $token = md5($employee->id . time() . uniqid());
        $cacheKey = 'user_token:' . md5($token);
        $expire = config('jwt.expire', 86400 * 7);

        $userData = [
            'id' => $employee->id,
            'code' => $employee->code,
            'name' => $employee->name,
            'email' => $employee->email,
            'role_id' => $employee->role_id,
            'department_id' => $employee->department_id,
            'is_super' => false,
            'permissions' => $employee->getPermissions(),
        ];

        Cache::set($cacheKey, $userData, $expire);

        return $token;
    }

    /**
     * 清除Token
     */
    public static function clearToken(string $token): void
    {
        $cacheKey = 'user_token:' . md5($token);
        Cache::delete($cacheKey);
    }
}
