<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiOperationLog
{
    /**
     * 操作日志中间件
     * 记录所有数据变更操作（POST/PUT/PATCH/DELETE）
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // 只记录数据变更请求
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $response;
        }

        // 只记录成功的请求（2xx 状态码）
        if (!$response->isSuccessful()) {
            return $response;
        }

        try {
            $this->logOperation($request, $response);
        } catch (\Exception $e) {
            // 日志记录失败不影响业务
        }

        return $response;
    }

    /**
     * 记录操作日志
     */
    private function logOperation(Request $request, $response)
    {
        $user = $request->user();
        if (!$user) {
            return;
        }

        // 从路由获取模块信息
        $route = $request->route();
        $routeName = $route ? ($route->getName() ?? $route->uri()) : $request->path();
        $module = $this->extractModule($routeName);

        // 获取操作类型
        $action = $this->getActionType($request->method(), $response);

        // 从请求体中提取目标类型和ID
        $targetType = $this->extractTargetType($request->path());
        $targetId = $this->extractTargetId($response);

        // 生成操作描述
        $description = $this->generateDescription($action, $module, $targetType, $targetId);

        DB::table('operation_logs')->insertGetId([
            'employee_id' => $user->id,
            'module' => $module,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'description' => $description,
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * 从路由提取模块名
     */
    private function extractModule($routeName)
    {
        $parts = explode('/', $routeName);
        $module = last(array_filter($parts));
        return $module ?? 'unknown';
    }

    /**
     * 获取操作类型
     */
    private function getActionType(string $method, $response)
    {
        if ($method === 'POST') {
            return 'create';
        }
        if ($method === 'DELETE') {
            return 'delete';
        }
        return 'update';
    }

    /**
     * 从路径提取目标类型
     */
    private function extractTargetType(string $path)
    {
        $parts = explode('/', trim($path, '/'));
        foreach ($parts as $part) {
            if (!is_numeric($part) && strpos($part, 'api') === false) {
                return ucfirst(rtrim($part, 's'));
            }
        }
        return 'Unknown';
    }

    /**
     * 从响应提取目标ID
     */
    private function extractTargetId($response)
    {
        $content = $response->getContent();
        if (!$content) {
            return null;
        }

        $data = json_decode($content, true);
        if (isset($data['data']['id'])) {
            return $data['data']['id'];
        }
        if (isset($data['id'])) {
            return $data['id'];
        }

        return null;
    }

    /**
     * 生成操作描述
     */
    private function generateDescription(string $action, string $module, string $targetType, ?int $targetId)
    {
        $actionMap = [
            'create' => '创建',
            'update' => '更新',
            'delete' => '删除',
        ];

        $actionText = $actionMap[$action] ?? $action;
        $idText = $targetId ? " (ID: {$targetId})" : '';

        return "{$actionText} {$targetType}{$idText}";
    }
}
