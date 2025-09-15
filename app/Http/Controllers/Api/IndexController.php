<?php

namespace App\Http\Controllers\Api;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Jiannei\Response\Laravel\Support\Facades\Response;

class IndexController extends ApiBaseController
{
    public function test(Request $request): JsonResponse
    {
        // Response::ok();
        // Response::fail();
        // Response::errorNotFound();
        // Response::errorUnauthorized();
        // Response::errorBadRequest();
        // Response::success([]);
        return Response::ok();
    }
}
