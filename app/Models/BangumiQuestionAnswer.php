<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class BangumiQuestionAnswer extends Model
{
    protected $table = 'bangumi_answers';

    protected $fillable = [
        'bangumi_slug',
        'user_slug',
        'question_id',
        'answer_id',
        'is_right'
    ];
}
