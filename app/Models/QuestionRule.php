<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class QuestionRule extends Model
{
    protected $fillable = [
        'tag_slug',
        'question_count',   // 答题的个数
        'right_rate',       // 正确率，0 ~ 100
        'qa_minutes',       // 答题的时长（分钟）
        'rule_type',        // 门槛类型：0 需要答题，1 只能邀请
        'result_type',      // 算分方式：0 答完之后出结果，1 每答一道告知结果
    ];

    public function tag()
    {
        return $this->belongsTo('App\Models\Tag', 'tag_slug', 'slug');
    }
}
