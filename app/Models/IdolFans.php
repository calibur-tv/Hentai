<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * 这个表记录 idol 每天的数据情况
 */
class IdolFans extends Model
{
    protected $table = 'idol_fans';

    protected $fillable = [
        'user_slug',
        'idol_slug',
        'coin_count',   // 投入的团子个数
        'stock_count',  // 每次入股时，根据当时的股价，算出得到的股票数，最终结算时，根据股票数 * 股价来计算盈亏
    ];
}
