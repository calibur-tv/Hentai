<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * 这个表记录 idol 每天的数据情况
 */
class IdolPriceTrend extends Model
{
    protected $table = 'idol_price_trends';

    protected $fillable = [
        'idol_slug',
        'lover_user_slug',
        'market_price',
        'stock_price',
        'fans_count',
        'coin_count'
    ];
}
