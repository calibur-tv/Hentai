<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class BangumiScore extends Model
{
    protected $table = 'bangumi_scores';

    protected $fillable = [
        'bangumi_slug',
        'user_slug',
        'user_age',
        'user_sex',
        'total',
        'lol',
        'cry',
        'fight',
        'moe',
        'sound',
        'vision',
        'role',
        'story',
        'express',
        'style'
    ];
}
