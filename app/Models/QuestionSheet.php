<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionSheet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_slug',
        'tag_slug',
        'questions_slug',
        'done_count',
        'result_type',      // 0.答题中，1.已通过 2.已失败 3.已超时
    ];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_slug', 'slug');
    }

    public function tag()
    {
        return $this->belongsTo('App\Models\Tag', 'tag_slug', 'slug');
    }
}
