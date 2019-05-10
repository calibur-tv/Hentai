<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-09
 * Time: 21:51
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'reportable_id',
        'reportable_type',
        'report_type'
    ];

    public function reporter()
    {
        return $this->belongsTo('App\User', 'id', 'user_id');
    }

    public function content()
    {
        return $this->morphOne('App\Models\Content', 'contentable');
    }

    public function reportable()
    {
        return $this->morphTo();
    }
}
