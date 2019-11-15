<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Idol extends Model
{
    protected $table = 'idols';

    protected $fillable = [
        'slug',
        'title',
        'alias',
        'intro',
        'avatar',
        'source_id',
        'bangumi_slug',
        'lover_slug',
        'is_newbie',
        'market_price',
        'stock_price',
        'fans_count',
        'coin_count'
    ];
}
