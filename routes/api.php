<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use Jiannei\Response\Laravel\Support\Facades\Response;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Api路由
Route::namespace('Api')->middleware(['auth.admin'])->group(function () {
    // ping
    Route::post('ping', function () {
        return Response::ok('pong');
    });
    // auth-模块
    Route::prefix('auth')->group(function () {
        // 不需要验证 JWT 的路由
        Route::withoutMiddleware('auth.admin')->group( function () {
            Route::post('login', [AuthController::class, 'login']);
            // Route::post('register', [AuthController::class, 'register']);
        });

        // 需要验证 JWT 的路由
        Route::group([], function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

});

