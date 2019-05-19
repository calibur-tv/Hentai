<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Relation\Traits\CanBeVoted;
use Spatie\Permission\Traits\HasRoles;

class Comment extends Model
{
    use SoftDeletes, CanBeVoted, HasRoles;

    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'pin_id',
        'trial_type',       // 审核结果，默认是 0，不在审核中
    ];

    protected $touches = ['pin'];

    public function from_user()
    {
        return $this->belongsTo('App\User', 'from_user_id', 'id');
    }

    public function to_user()
    {
        return $this->belongsTo('App\User', 'to_user_id', 'id');
    }

    public function pin()
    {
        return $this->belongsTo('App\Models\Pin', 'pin_id', 'id');
    }

    public function content()
    {
        return $this->morphOne('App\Models\Content', 'contentable');
    }

    public function reports()
    {
        return $this->morphMany('App\Models\Report', 'reportable');
    }
}
