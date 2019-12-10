<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class BangumiQuestionSheet extends Model
{
    protected $table = 'bangumi_question_sheet';

    protected $fillable = [
        'bangumi_slug',
        'user_slug',
        'question_ids',
        'result_type',  // 0.答题中，1.已通过 2.已失败 3.已超时
    ];
}
