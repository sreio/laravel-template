<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticate;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class AdminUser extends Authenticate implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'admin_users';

    protected $hidden = ['password'];

    protected $fillable = ['nickname', 'username', 'password', 'status', 'role_id'];

    /**
     * 获取会储存到 jwt 声明中的标识
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * 返回包含要添加到 jwt 声明中的自定义键值对数组
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    // 密码加密
    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = bcrypt($value);
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

}
