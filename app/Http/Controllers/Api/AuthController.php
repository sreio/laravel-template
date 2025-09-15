<?php

namespace App\Http\Controllers\Api;

use App\Constant\Common;
use App\Constant\CommonCache;
use App\Models\AdminUser;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Jiannei\Response\Laravel\Support\Facades\Response;

class AuthController extends ApiBaseController
{
    protected string $tokenPrefix = 'Bearer ';
    /**
     * 登录
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate(
            [
                'username' => 'required',
                'password' => 'required',
            ],
            [
                'username.required' => '请输入用户名',
                'password.required' => '请输入密码',
            ]
        );

        $credentials = $request->only(['username', 'password']);
        $account = sprintf(CommonCache::ADMIN_LOGIN_LOCK, hash('md5', $credentials['username']));

        if (empty(Cache::get($account))) {
            Cache::put($account, 0, Common::INPUT_LOCK_TIME);
        }
        if (Cache::get($account) >= Common::INPUT_ERROR_NUM) {
            return Response::fail('输入错误次数过多，请您稍后重试');
        }

        if (!$token = Auth('admin')->attempt($credentials)) {
            Cache::increment($account);
            return Response::fail('账户或密码错误', 402);
        }

        return Response::success([
            'access_token' => $this->tokenPrefix . $token,
            'expires_in' => Auth('admin')->factory()->getTTL() * 60,
            'user' => auth('admin')->user(),
        ]);
    }

    /**
     * 注册
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:admin_users',
            'password' => 'required|min:6',
            'nickname' => 'required',
        ]);

        if ($validator->fails()) {
            return Response::fail($validator->errors()->first(), 400);
        }

        try {
            $user = AdminUser::query()->create($validator->validated());
        } catch (ValidationException | Exception $e) {
            return Response::fail($e->getMessage());
        }

        return Response::success([
            'access_token' => $this->tokenPrefix . Auth('admin')->attempt($validator->validated()),
            'expires_in' => Auth('admin')->factory()->getTTL() * 60,
            'user' => auth('admin')->user(),
        ]);
    }

    /**
     * 获取用户信息
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        $user = Auth('admin')->user();
        // todo: 临时处理方案
        $user->roles = $user->id === 1 ? ['admin'] : ['other'];
        return Response::success($user);
    }

    /**
     * 注销
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        Auth('admin')->logout();
        return Response::ok('Successfully logged out');
    }

    /**
     * 刷新 token
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            return Response::success([
                'access_token' => $this->tokenPrefix . Auth('admin')->refresh(),
                'expires_in' => Auth('admin')->factory()->getTTL() * 60
            ]);
        } catch (Exception $e) {
            return Response::fail('Unable to refresh token');
        }
    }
}
