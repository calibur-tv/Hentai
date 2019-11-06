<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * 偶像表放在 tag 表里
 * 这个 model 记录 idol 的大股东、股东个数，金币个数，市值，股价
 */
class IdolExtra extends Model
{
    protected $table = 'idol_extras';

    protected $fillable = [
        'idol_slug',
        'lover_user_slug',
        'fans_count',
        'coin_count',
        'stock_price',
        'market_price'
    ];
}
