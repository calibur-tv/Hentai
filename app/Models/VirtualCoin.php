<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2018/7/11
 * Time: 下午3:56
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VirtualCoin extends Model
{
    use SoftDeletes;

    /**
     * channel_type
     * 0 => 签到
     * 1 => 打赏帖子
     * 2 => 入股偶像
     * 3 => 用户活跃送团子
     * 4 => 管理活跃送光玉
     * 5 => 给用户赠送团子
     * 6 => 给用户赠送光玉
     * 7 => 给邀请他人注册的人送团子
     * 8 => 给被邀请注册的用户送团子
     */
    protected $fillable = [
        'amount',
        'user_slug',
        'about_user_slug',
        'channel_type',
        'product_slug',
        'message'
    ];
}
