<?php

use Illuminate\Http\Request;
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

/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});*/
Route::any('/ping', function () {
    return Response::ok('pong');
});
