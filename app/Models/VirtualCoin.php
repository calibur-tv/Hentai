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
