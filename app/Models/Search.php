<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Search extends Model
{
    protected $table = 'searches';
    /**
     * type：
     * 0 => user
     * 1 => tag
     * 2 => pin
     */
    protected $fillable = [
        'type',
        'slug',
        'text',
        'score'
    ];
}
