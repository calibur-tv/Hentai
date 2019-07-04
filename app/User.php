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
use Mews\Purifier\Facades\Purifier;
use Spatie\Permission\Traits\HasRoles;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, SoftDeletes, HasRoles,
        CanFollow, CanLike, CanBookmark, CanFavorite, CanVote, CanSubscribe,
        CanBeFollowed;

    protected $guard_name = 'api';

    protected $fillable = [
        'slug',
        'phone',
        'nickname',
        'avatar',
        'banner',
        'signature',
        'birthday',
        'birth_secret',
        'sex',
        'sex_secret',
        'password',
        'api_token',
        'qq_open_id',
        'qq_unique_id',
        'wechat_unique_id',
        'wechat_open_id',
        'title',                        // 头衔
        'level',                        // 等级
        'virtual_coin',                 // 团子数量
        'money_coin',                   // 光玉数量
        'banned_to',                    // 封禁结束时间
        'continuous_sign_count',        // 连续签到次数
        'total_sign_count',             // 总签到次数
        'latest_signed_at',             // 最后签到时间
        'activity_stat',                // 活跃度统计
        'exposure_stat',                // 曝光度统计
        'migration_state',
        'followers_count',              // 粉丝数量
        'following_count',              // 关注数量
        'visit_count',                  // 访问量
    ];

    protected $hidden = [
        'password',
        'api_token'
    ];

    protected $casts = [
        'sex' => 'integer',
        'sex_secret' => 'boolean',
        'birth_secret' => 'boolean',
    ];

    public function setNicknameAttribute($name)
    {
        $this->attributes['nickname'] = Purifier::clean($name);
    }

    public function setSignatureAttribute($signature)
    {
        $this->attributes['signature'] = Purifier::clean($signature);
    }

    public function setAvatarAttribute($url)
    {
        $this->attributes['avatar'] = trimImage($url);
    }

    public function setBannerAttribute($url)
    {
        $this->attributes['banner'] = trimImage($url);
    }

    public function setPasswordAttribute($pwd)
    {
        $this->attributes['password'] = Hash::make($pwd);
    }

    public function getAvatarAttribute($avatar)
    {
        return patchImage($avatar, 'default-avatar');
    }

    public function getBannerAttribute($banner)
    {
        return patchImage($banner, 'default-banner');
    }

    public function getNicknameAttribute($name)
    {
        return $name ? trim($name) : '空白';
    }

    public function getTitleAttribute($title)
    {
        return $title ? json_decode($title) : [];
    }

    public function getSignatureAttribute($text)
    {
        return $text ? trim($text) : '这个人还很神秘...';
    }

    public function pins()
    {
        return $this->hasMany('App\Models\Pin');
    }

    public function tags()
    {
        return $this->morphToMany('App\Models\Tag', 'taggable');
    }

    public function timeline()
    {
        /**
         * 用户注册 -> 0
         * 收藏分区 -> 1
         * 创建分区 -> 2
         * 创建文章 -> 3
         */
        return $this->morphMany('App\Models\Timeline', 'timelineable');
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
        $token = id2slug($this->id) . ':' . str_replace(':', '-', $token);

        $this->update([
            'api_token' => $token
        ]);

        return $token;
    }

    public static function createUser($data)
    {
        $user = self::create($data);
        $slug = 'cc-' . id2slug($user->id);
        if (isset($data['nickname']))
        {
            $user->update([
                'slug' => $slug,
                'migration_state' => 6
            ]);
        }
        else
        {
            $user->update([
                'slug' => $slug,
                'nickname' => $slug,
                'migration_state' => 6
            ]);
        }
        $user->slug = $slug;
        $user->api_token = $user->createApiToken();
        $user->invitor_slug = $data['invitor_slug'] ?? '';

        event(new \App\Events\User\Register($user));

        return $user;
    }

    public function updateProfile(array $data)
    {
        $this->update($data);

        event(new \App\Events\User\UpdateProfile($this));
    }
}
