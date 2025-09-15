<?php

namespace App\Constant;

class Common
{
    /**
     * 页
     */
    public const PAGE = 1;

    /**
     * 页码
     */
    public const PAGE_SIZE = 15;

    public const PAGE_SIZE_MAX = 100;

    /**
     * 登录、密码找回输入错误次数
     */
    const INPUT_ERROR_NUM = 5;

    /**
     * 登录、密码找回输入错误锁定秒数
     */
    const INPUT_LOCK_TIME = 1800;

}
