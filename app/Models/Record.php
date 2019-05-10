<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-21
 * Time: 14:35
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'recordable_id',
        'recordable_type',
        'value',
        'type',
        'day'
    ];
}
