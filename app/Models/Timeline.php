<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timeline extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'timelineable_id',
        'timelineable_type',
        'event_type',
        'event_slug'
    ];

    public function timelineable()
    {
        return $this->morphTo();
    }
}
