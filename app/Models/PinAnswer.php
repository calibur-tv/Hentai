<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class PinAnswer extends Model
{
    protected $fillable = [
        'pin_slug',
        'user_slug',
        'selected_uuid',
        'is_right'
    ];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_slug', 'slug');
    }

    public function pin()
    {
        return $this->belongsTo('App\Models\Pin', 'pin_slug', 'slug');
    }
}
