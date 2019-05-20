<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use App\Services\Relation\Traits\CanFollow;
use App\Services\Relation\Traits\CanLike;
use App\Services\Relation\Traits\CanBookmark;
use App\Services\Relation\Traits\CanFavorite;
use App\Services\Relation\Traits\CanVote;
use App\Services\Relation\Traits\CanSubscribe;
use Spatie\Permission\Traits\HasRoles;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, SoftDeletes, HasRoles,
        CanFollow, CanLike, CanBookmark, CanFavorite, CanVote, CanSubscribe;

    protected $guard_name = 'api';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'password',
        'nickname',
        'avatar',
        'slug',
        'api_token',
        'is_admin'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'api_token'
    ];

    public function pins()
    {
        return $this->hasMany('App\Models\Pin');
    }

    public function tags()
    {
        return $this->morphToMany('App\Models\Tag', 'taggable');
    }

    public function comments()
    {
        return $this->hasMany('App\Models\Comment');
    }

    public function reports()
    {
        return $this->morphMany('App\Models\Report', 'reportable');
    }
}
