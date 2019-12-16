<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class BangumiQuestion extends Model
{
    protected $table = 'bangumi_questions';

    protected $fillable = [
        'bangumi_slug',
        'user_slug',
        'title',
        'answers',
        'right_id',
        'like_count',
        'status',       // 0 待入库，1 已入库，2 已删除
    ];

    public function author()
    {
        return $this->belongsTo('App\User', 'user_slug', 'slug');
    }

    public function bangumi()
    {
        return $this->belongsTo('App\Models\Bangumi', 'bangumi_slug', 'slug');
    }
}
