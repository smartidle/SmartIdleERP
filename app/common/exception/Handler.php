<?php

namespace app\common\exception;

use think\App;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 全局异常处理器
 */
class Handler extends Handle
{
    /**
     * 渲染错误视图的路径
     */
    protected string $errorView = 'error';

    /**
     * 错误消息
     */
    protected string $errorMessage = '';

    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): Response
    {
        // API请求返回JSON
        if ($request->isAjax() || $request->isPost()) {
            return $this->renderJson($e);
        }

        // 其他请求尝试渲染视图
        return $this->renderView($e);
    }

    /**
     * 渲染JSON错误响应
     */
    protected function renderJson(Throwable $e): Response
    {
        $code = $this->getCode($e);
        $message = $this->getMessage($e);

        $data = [
            'code' => $code,
            'msg' => $message,
            'time' => time(),
        ];

        // 调试模式下显示详细信息
        if ($this->app->isDebug()) {
            $data['debug'] = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];
        }

        return json($data, $code >= 1000 ? 200 : $code);
    }

    /**
     * 渲染视图错误页面
     */
    protected function renderView(Throwable $e): Response
    {
        $code = $this->getCode($e);
        $message = $this->getMessage($e);

        // 调试模式显示详细错误
        if ($this->app->isDebug()) {
            return response($this->renderExceptionDetail($e), 500);
        }

        // 生产模式显示友好错误页面
        $errorView = $this->app->getAppPath() . $this->errorView . '.html';

        if (file_exists($errorView)) {
            return view('error', [
                'code' => $code,
                'msg' => $message,
                'time' => time(),
            ]);
        }

        // 默认错误页面
        return response("<html><body><h1>Error {$code}</h1><p>{$message}</p></body></html>", 500);
    }

    /**
     * 渲染异常详细信息（调试模式）
     */
    protected function renderExceptionDetail(Throwable $e): string
    {
        $html = '<html><head><title>Error</title>';
        $html .= '<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}';
        $html .= '.error{background:#fff;padding:20px;border-radius:5px;margin:10px 0;}';
        $html .= '.error h2{color:#e74c3c;margin-top:0;}';
        $html .= '.trace{background:#2c3e50;color:#ecf0f1;padding:15px;border-radius:5px;overflow-x:auto;}';
        $html .= '.trace pre{margin:0;white-space:pre-wrap;}</style></head><body>';
        $html .= '<div class="error"><h2>' . $this->getCode($e) . ': ' . htmlspecialchars($this->getMessage($e)) . '</h2>';
        $html .= '<p><strong>File:</strong> ' . $e->getFile() . ':' . $e->getLine() . '</p>';
        $html .= '</div>';
        $html .= '<div class="trace"><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre></div>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * 获取异常代码
     */
    protected function getCode(Throwable $e): int
    {
        $code = $e->getCode();
        if ($code >= 100 && $code < 600) {
            return $code;
        }
        if ($e instanceof ValidateException) {
            return 422;
        }
        if ($e instanceof HttpException) {
            return $code ?: $e->getStatusCode();
        }
        return 500;
    }

    /**
     * 获取异常消息
     */
    protected function getMessage(Throwable $e): string
    {
        $message = $e->getMessage();
        if ($e instanceof ValidateException) {
            return $message ?: '参数验证失败';
        }
        if ($e instanceof HttpException) {
            return $message ?: 'HTTP异常';
        }
        return $message ?: '服务器内部错误';
    }

    /**
     * 判断是否为HTTP异常
     */
    protected function isHttpException(Throwable $e): bool
    {
        return $e instanceof HttpException;
    }
}
