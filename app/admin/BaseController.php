<?php

namespace app\admin;

use app\common\exception\BusinessException;
use app\common\exception\ValidateException;
use think\App;
use think\exception\HttpResponseException;
use think\Response;
use think\Validate;

/**
 * 基础控制器
 */
abstract class BaseController
{
    /**
     * 应用实例
     */
    protected App $app;

    /**
     * 请求实例
     */
    protected $request;

    /**
     * 响应数据
     */
    protected array $result = [
        'code' => 0,
        'msg' => 'success',
        'time' => 0,
        'data' => null,
    ];

    /**
     * 构造函数
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;

        // 初始化
        $this->initialize();
    }

    /**
     * 初始化
     */
    protected function initialize(): void
    {
    }

    /**
     * 返回成功响应
     */
    protected function success($data = null, string $msg = 'success', int $code = 0): Response
    {
        $this->result['code'] = $code;
        $this->result['msg'] = $msg;
        $this->result['time'] = time();
        $this->result['data'] = $data;

        return json($this->result);
    }

    /**
     * 返回失败响应
     */
    protected function error(string $msg = 'error', int $code = 400, $data = null): Response
    {
        $this->result['code'] = $code;
        $this->result['msg'] = $msg;
        $this->result['time'] = time();
        $this->result['data'] = $data;

        return json($this->result);
    }

    /**
     * 返回分页响应
     */
    protected function paginate(array $list, int $total, int $page = 1, int $pageSize = 20): Response
    {
        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => ceil($total / $pageSize),
        ]);
    }

    /**
     * 验证数据
     *
     * @param array $data 验证数据
     * @param string|array $validate 验证器类名或验证规则数组
     * @param array $message 错误消息
     * @param bool $batch 是否批量验证
     * @return array
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false): array
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
            if ($message) {
                $v->message($message);
            }
            $validate = $v;
        } elseif (is_string($validate) && class_exists($validate)) {
            $validate = new $validate();
        }

        if (!$validate->batch($batch)->check($data)) {
            throw new ValidateException($validate->getError());
        }

        return $data;
    }

    /**
     * 获取分页参数
     */
    protected function getPageParams(): array
    {
        $page = max(1, intval($this->request->param('page', 1)));
        $pageSize = max(1, min(100, intval($this->request->param('page_size', 20))));

        return [$page, $pageSize];
    }

    /**
     * 获取排序参数
     */
    protected function getSortParams(): array
    {
        $order = $this->request->param('order', 'id');
        $sort = $this->request->param('sort', 'desc');

        return [$order, strtolower($sort) === 'asc' ? 'asc' : 'desc'];
    }

    /**
     * 获取查询筛选参数
     */
    protected function getFilters(): array
    {
        $filters = $this->request->param('filter', '');
        if (empty($filters)) {
            return [];
        }

        if (is_string($filters)) {
            $filters = json_decode($filters, true) ?: [];
        }

        return $filters;
    }

    /**
     * 快速获取参数
     */
    protected function param(string $name = '', $default = null)
    {
        return $this->request->param($name, $default);
    }

    /**
     * 快速获取POST参数
     */
    protected function post(string $name = '', $default = null)
    {
        return $this->request->post($name, $default);
    }

    /**
     * 快速获取GET参数
     */
    protected function get(string $name = '', $default = null)
    {
        return $this->request->get($name, $default);
    }

    /**
     * 抛出业务异常
     */
    protected function throwBusinessException(string $message, int $code = 400, array $data = []): void
    {
        throw new BusinessException($message, $code, $data);
    }
}
