<?php

namespace app\admin\middleware;

use app\model\OperationLog;
use think\App;
use think\Request;

/**
 * 操作日志中间件
 */
class OperationLog
{
    /**
     * 需要记录的操作
     */
    protected array $logActions = ['add', 'edit', 'delete', 'save', 'update', 'remove'];

    /**
     * 不需要记录的路由
     */
    protected array $noLogRoutes = [
        'admin/login/index',
        'admin/login/captcha',
        'admin/index/index',
        'admin/index/welcome',
    ];

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 中间件处理
     */
    public function handle(Request $request, \Closure $next)
    {
        $response = $next($request);

        // 只记录POST/PUT/DELETE请求
        if (in_array(strtolower($request->method()), ['post', 'put', 'delete'])) {
            $this->writeLog($request);
        }

        return $response;
    }

    /**
     * 写入操作日志
     */
    protected function writeLog(Request $request): void
    {
        $route = strtolower($request->module() . '/' . $request->controller() . '/' . $request->action());

        // 跳过不需要记录的路由
        if (in_array($route, $this->noLogRoutes)) {
            return;
        }

        try {
            $userData = $request->userData ?? [];
            $employeeId = $userData['id'] ?? 0;

            // 解析模块和操作
            $module = $request->module();
            $action = $this->parseAction($request->method());

            // 获取目标信息
            $targetType = $request->controller();
            $targetId = $request->param('id', 0);

            // 构建描述
            $description = sprintf(
                '%s %s %s',
                $this->getEmployeeName($employeeId),
                $action,
                $this->getModuleName($route)
            );

            // 异步写入日志（使用fastcgi_finish_request如果可用）
            if (function_exists('fastcgi_finish_request')) {
                // 创建日志记录
                $logData = [
                    'employee_id' => $employeeId,
                    'module' => $module,
                    'action' => $action,
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'description' => $description,
                    'ip' => $request->ip(),
                    'method' => $request->method(),
                    'url' => $request->url(true),
                    'params' => json_encode($request->param(), JSON_UNESCAPED_UNICODE),
                    'create_time' => time(),
                ];

                OperationLog::create($logData);
            }
        } catch (\Throwable $e) {
            // 静默处理日志记录异常
        }
    }

    /**
     * 解析操作类型
     */
    protected function parseAction(string $method): string
    {
        return match (strtoupper($method)) {
            'POST' => '创建',
            'PUT' => '更新',
            'DELETE' => '删除',
            default => '操作',
        };
    }

    /**
     * 获取员工名称
     */
    protected function getEmployeeName(int $employeeId): string
    {
        if ($employeeId <= 0) {
            return '系统';
        }
        return '员工#' . $employeeId;
    }

    /**
     * 获取模块名称
     */
    protected function getModuleName(string $route): string
    {
        $routeMap = [
            'admin/product/index' => '产品',
            'admin/product/save' => '产品',
            'admin/product/update' => '产品',
            'admin/product/delete' => '产品',
            'admin/order/index' => '销售订单',
            'admin/order/save' => '销售订单',
            'admin/order/update' => '销售订单',
            'admin/order/delete' => '销售订单',
            'admin/purchase/index' => '采购订单',
            'admin/purchase/save' => '采购订单',
            'admin/warehouse/index' => '仓库',
            'admin/inventory/index' => '库存',
            'admin/customer/index' => '客户',
            'admin/supplier/index' => '供应商',
            'admin/employee/index' => '员工',
            'admin/role/index' => '角色',
        ];

        return $routeMap[$route] ?? '数据';
    }
}
