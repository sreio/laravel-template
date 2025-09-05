<?php

namespace App\Constant;

class ErrorCode
{
    public const SUCCESS = 200;
    public const FAIL = 400;
    public const SERVICE_ERROR = 500;

    // 自定义状态码：200、400、500开头，后拼接自定义 3 位

    public const Validate_Error = 400000; // 验证错误
    public const Validate_IS_EXIST = 400100; // 已存在
    public const Validate_NOT_EXIST = 400200; // 不存在

}
