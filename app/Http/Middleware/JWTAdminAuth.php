<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Jiannei\Response\Laravel\Support\Facades\Response;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class JWTAdminAuth extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        $this->checkForToken($request);

        try {
            if ($this->auth->parseToken()->authenticate()) {
                return $next($request);
            }
            Response::errorUnauthorized('用户无效');
        } catch (TokenExpiredException $e) {
            try {
                // 刷新用户的 token
                $token = $this->auth->refresh();
                // 使用一次性登录以保证此次请求的成功
                Auth::guard('admin')->onceUsingId($this->auth->manager()->getPayloadFactory()->buildClaimsCollection()->toPlainArray()['sub']);
                // 在响应头中返回新的 token
                return $this->setAuthenticationHeader($next($request), $token);
            } catch (TokenBlacklistedException $e) {
                Response::errorUnauthorized('token 已被注销');
            } catch (JWTException $e) {
                Response::errorUnauthorized('token 无效');
            }
        } catch (TokenInvalidException  $e) {
            Response::errorUnauthorized('token 无效');
        } catch (JWTException $e) {
            Response::errorUnauthorized('缺少token');
        }
    }
}
