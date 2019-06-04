<?php

namespace App;

use App\Services\Relation\Traits\CanBeFollowed;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
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
        CanFollow, CanLike, CanBookmark, CanFavorite, CanVote, CanSubscribe,
        CanBeFollowed;

    protected $guard_name = 'api';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'slug',
        'nickname',
        'avatar',
        'banner',
        'birthday',
        'birth_secret',
        'phone',
        'sex',
        'sex_secret',
        'signature',
        'level',
        'password',
        'api_token',
        'qq_open_id',
        'qq_unique_id',
        'wechat_unique_id',
        'wechat_open_id',
        'virtual_coin',
        'money_coin',
        'banned_to',
        'migration_state',
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

    protected $casts = [
        'sex' => 'integer',
        'sex_secret' => 'boolean',
        'birth_secret' => 'boolean',
    ];

    public function setAvatarAttribute($url)
    {
        $arr = explode('calibur.tv/', $url);

        return count($arr) === 1 ? $url : explode('calibur.tv/', $url)[1];
    }

    public function setBannerAttribute($url)
    {
        $arr = explode('calibur.tv/', $url);

        return count($arr) === 1 ? $url : explode('calibur.tv/', $url)[1];
    }

    public function getAvatarAttribute($avatar)
    {
        return config('app.image-cdn')[array_rand(config('app.image-cdn'))]. ($avatar ?: 'default-avatar');
    }

    public function getBannerAttribute($banner)
    {
        return config('app.image-cdn')[array_rand(config('app.image-cdn'))]. ($banner ?: 'default-banner');
    }

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

    /**
     * 确认密码是否正确
     *
     * @param string $password
     * @return bool
     */
    public function verifyPassword(string $password): bool
    {
        return Hash::check($password, $this->password);
    }

    public function createApiToken()
    {
        $token = Crypt::encrypt($this->slug . time());

        $this->update([
            'api_token' => $token
        ]);

        return $token;
    }

    public static function createUser($data)
    {
        $user = self::create($data);
        $slug = $user->id2slug($user->id);
        $user->update([
            'slug' => $slug
        ]);
        $user->slug = $slug;
        $user->api_token = $user->createApiToken();

        return $user;
    }

    protected function id2slug($id)
    {
        return base_convert(($id * 1000 + rand(0, 999)), 10, 36);
    }
}
