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
        'idol_id',
        'star_count',
        'total_price',
    ];
}
