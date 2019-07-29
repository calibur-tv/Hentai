<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Search extends Model
{
    protected $table = 'searches';
    /**
     * type：
     * 1 => tag
     * 2 => pin
     * 3 => user
     */
    protected $fillable = [
        'type',
        'slug',
        'text',
        'score'
    ];
}
