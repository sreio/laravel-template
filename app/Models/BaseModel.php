<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static active(int $status = self::STATUS_DISABLED) Builder
 */
class BaseModel extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    // 状态
    public const STATUS_DELETED = -1;
    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;


    /**
     * 状态筛选
     *
     * @param Builder $query
     * @param int $status
     * @return Builder
     */
    static public function scopeActive(Builder $query, int $status = self::STATUS_DISABLED): Builder
    {
        return $query->where('status', $status);
    }
}
