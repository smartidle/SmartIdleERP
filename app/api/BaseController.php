<?php

namespace app\api;

use app\common\exception\BusinessException;
use think\App;
use think\Request;
use think\Response;

/**
 * API基础控制器
 */
abstract class BaseController
{
    protected App $app;
    protected Request $request;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $app->request;
    }

    /**
     * 返回成功响应
     */
    protected function success($data = null, string $msg = 'success', int $code = 0): Response
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'time' => time(),
            'data' => $data,
        ]);
    }

    /**
     * 返回失败响应
     */
    protected function error(string $msg = 'error', int $code = 400, $data = null): Response
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'time' => time(),
            'data' => $data,
        ]);
    }
}
