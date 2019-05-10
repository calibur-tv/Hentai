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
     * 1 => 邀请注册
     * 2 => 普通用户活跃送团子
     * 3 => 吧主活跃送团子
     * 4 => 打赏帖子
     * 5 => 打赏相册
     * 6 => 打赏漫评
     * 7 => 打赏回答
     * 8 => 打赏视频
     * 9 => 应援偶像
     * 10 => 提现
     * 11 => 视频被删除
     * 12 => 回答被删除
     * 13 => 漫评被删除
     * 14 => 相册被删除
     * 15 => 帖子被删除
     * 16 => 给用户赠送团子
     * 17 => 给用户赠送光玉
     * 18 => 活跃用户送光玉
     * 19 => 活跃吧主送光玉
     * 20 => 被人邀请注册送团
     * 21 => 承包季度视频
     * 22 => 偶像股份交易
     * 23 => 视频被承包
     * 24 => 偶像产品交易
     * 25 => 打赏帖子赞助了偶像
     */
    protected $fillable = [
        'amount',
        'user_id',
        'about_user_id',
        'channel_type',
        'product_id',
        'message'
    ];
}
