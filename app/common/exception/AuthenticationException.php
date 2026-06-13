<?php

namespace app\common\exception;

use think\Exception;

/**
 * 认证异常
 */
class AuthenticationException extends Exception
{
    /**
     * 错误码
     */
    protected int $errorCode = 401;

    /**
     * 构造函数
     */
    public function __construct(string $message = '未认证', int $code = 401)
    {
        parent::__construct($message, $code);
        $this->errorCode = $code;
    }

    /**
     * 获取错误码
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }
}
