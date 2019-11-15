<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Bangumi extends Model
{
    protected $table = 'bangumis';

    protected $fillable = [
        'title',
        'alias',
        'intro',
        'avatar',
        'source_id',
        'parent_id',
        'is_parent'
    ];

    protected $casts = [
        'is_parent' => 'boolean'
    ];
}
