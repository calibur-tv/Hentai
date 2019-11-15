<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Idol extends Model
{
    protected $table = 'idols';

    protected $fillable = [
        'title',
        'alias',
        'intro',
        'avatar',
        'source_id',
        'bangumi_id',
        'is_newbie',
        'lover_slug',
        'market_price',
        'stock_price',
        'fans_count',
        'coin_count'
    ];
}
