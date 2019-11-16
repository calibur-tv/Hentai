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
     * 4 => bangumi
     * 5 => idol
     */
    protected $fillable = [
        'type',
        'slug',
        'text',
        'score'
    ];
}
