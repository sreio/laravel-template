# Laravel 8.x 模板

## 部署

```shell
git clone -b 8.x https://github.com/sreio/laravel-template.git your-project-name
cd your-project-name
rm -rf .git

# 开发环境
cp .env.example .env
composer install

# 生产环境
cp .env.example .env
composer install --optimize-autoloader --no-dev
# 使用JWT时请生成密钥
php artisan jwt:secret

chmod -R 775 storage
chmod -R 775 bootstrap/cache

## [线上] 优化
php artisan optimize
# 或者分别执行
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 修改记录

- [API响应格式调整](app/Http/Controllers/Api/IndexController.php)
- [统一请求处理](app/Services/ApiRequest.php)
- [定义SQL宏&route生成协议调整](app/Providers/AppServiceProvider.php:34)
- 公共定义参数
  - [公共参数](app/Constant/Common.php)
  - [缓存参数](app/Constant/CommonCache.php)
  - [错误码](app/Constant/ErrorCode.php)
- [基础模型](app/Models/BaseModel.php)
- 配置文件
  - 修改时区为 `Asia/Shanghai`

## JWT

### 用户表
- [SQL：admin_users](database/sql/admin_users.sql)

## packages
- [jiannei/laravel-response](https://github.com/jiannei/laravel-response)
- [tymon/jwt-auth](https://github.com/tymondesigns/jwt-auth)
