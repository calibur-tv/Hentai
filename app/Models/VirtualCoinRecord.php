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

    protected $fillable = [
        'from_user_slug',   // 消费者 slug
        'to_user_slug',     // 收费者 slug
        'target_slug',      // 收费产品 slug
        'target_type',      // 产品的类型
        'order_amount'      // 订单的金额
    ];
}
