<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Bangumi extends Model
{
    protected $table = 'bangumis';

    protected $fillable = [
        'slug',
        'title',
        'alias',
        'intro',
        'avatar',
        'source_id',
        'parent_slug',
        'is_parent'
    ];

    protected $casts = [
        'is_parent' => 'boolean'
    ];
}
