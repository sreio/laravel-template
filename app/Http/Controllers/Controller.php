<?php

namespace App\Http\Controllers;

use App\Constant\Common;
use Exception;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\ValidationException;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * 通用分页方法 - 返回自定义数组格式
     *
     * @param \Illuminate\Database\Eloquent\Builder|Builder $query 查询构造器
     * @param int $defaultPageSize 默认每页条数
     * @return array
     */
    public function paginateData(\Illuminate\Database\Eloquent\Builder|Builder $query, int $defaultPageSize = Common::PAGE_SIZE): array
    {
        // 从请求中获取页码和每页条数参数，默认为 1 和 $defaultPageSize
        $page = (int) request()->input('page', Common::PAGE);
        $pageSize = (int) request()->input('page_size', $defaultPageSize);

        // 获取总记录数
        $total = $query->count();

        // 当前页数据
        $results = $query->forPage($page, $pageSize)->get();

        // 计算最后一页数
        $lastPage = (int) ceil($total / $pageSize);

        // 返回数据和分页信息
        return [
            'list' => $results,
            'pagination' => [
                'page'       => $page,
                'page_size'  => $pageSize,
                'total'      => $total,
                'last_page'  => $lastPage,
            ],
        ];
    }


    /**
     * 获取参数
     * @throws Exception
     */
    public function commonGetParams(Validator $validator): array
    {
        try {
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first(), 40000);
            }

            return $validator->validated();
        } catch (ValidationException $e) {
            throw new Exception($e->getMessage(), 40000);
        }
    }
}
