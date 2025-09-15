<?php

namespace App\Providers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 定义sql宏
        Builder::macro('sql', function () {
            return array_reduce($this->getBindings(), function ($sql, $binding) {
                return preg_replace('/\?/', is_numeric($binding) ? $binding : "'" . $binding . "'", $sql, 1);
            }, $this->toSql());
        });
        // Eloquent ORM
        \Illuminate\Database\Eloquent\Builder::macro('sql', function () {
            return ($this->getQuery()->sql());
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * 在启用了 负载均衡 / Nginx 代理 / Cloudflare 等反向代理时
         * 如果外部访问是 HTTPS，而内部转发给 Laravel 可能是 HTTP
         * Laravel 默认会生成 http:// 链接
         *
         * 用户->Cloudflare(443)->Nginx(80)->Laravel(80) 那么Laravel会生成 http://
         */
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
