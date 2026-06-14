<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * 权限控制中间件
     * 检查用户角色是否有所需权限
     */
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // 管理员拥有所有权限
        if ($user->role && $user->role->code === 'super_admin') {
            return $next($request);
        }

        // 如果没有指定权限码，放行（由控制器内部判断）
        if (!$permission) {
            return $next($request);
        }

        // 获取用户角色的所有权限
        $role = $user->role;
        if (!$role) {
            return response()->json(['success' => false, 'message' => 'No role assigned'], 403);
        }

        $permissions = $role->permissions->pluck('code')->toArray();

        if (!in_array($permission, $permissions)) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied: ' . $permission,
            ], 403);
        }

        return $next($request);
    }
}
