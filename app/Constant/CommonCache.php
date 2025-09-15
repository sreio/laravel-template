<?php
namespace App\Constant;
class CommonCache
{
    const CACHE_MONTH_TTL = 86400 * 30;
    const CACHE_DAY_TTL = 86400;
    const CACHE_HOUR_TTL = 3600;
    const CACHE_THROUGH_TTL = 30;//防止击穿时间

    const ADMIN_LOGIN_LOCK = 'admin_login_lock:%s';
}

