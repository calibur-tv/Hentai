<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Content extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contentable_id',
        'contentable_type',
        'text'
    ];

    public function contentable()
    {
        return $this->morphTo();
    }
}
