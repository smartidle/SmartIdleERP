<?php

namespace app\common\exception;

use Exception;

/**
 * 业务异常
 */
class BusinessException extends Exception
{
    /**
     * 错误码
     */
    protected int $errorCode = 400;

    /**
     * 附加数据
     */
    protected array $data = [];

    /**
     * 构造函数
     */
    public function __construct(string $message = '', int $code = 400, array $data = [], ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $code;
        $this->data = $data;
    }

    /**
     * 获取错误码
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * 获取附加数据
     */
    public function getData(): array
    {
        return $this->data;
    }
}
