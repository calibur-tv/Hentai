<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2018/7/11
 * Time: 下午3:56
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualCoinRecord extends Model
{
    protected $table = 'virtual_coin_records';

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
        'from_user_slug',   // 消费者 slug
        'to_user_slug',     // 收费者 slug
        'target_slug',      // 收费产品 slug
        'target_type',      // 产品的类型
        'order_amount'      // 订单的金额
    ];
}
