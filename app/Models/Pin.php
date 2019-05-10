<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-09
 * Time: 21:51
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Relation\Traits\CanBeBookmarked;
use App\Services\Relation\Traits\CanBeFavorited;
use App\Services\Relation\Traits\CanBeVoted;

class Pin extends Model
{
    use SoftDeletes,
        CanBeVoted, CanBeBookmarked, CanBeFavorited;

    protected $fillable = [
        'slug',
        'title',
        'user_id',
        'trial_type',       // 进入审核池的类型，默认 0 不在审核池
        'comment_type',     // 主评论权限的类型
        // 'copyright_type',// 版权授权方式
        // 'is_create',     // 是否原创
        'is_locked',        // 审核不通过（已删除）
        'is_secret',        // 加密的（需要动态密码）
        'last_top_at',      // 最后置顶时间
        'published_at',     // 公开时间
        'recommended_at',   // 推荐的时间
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'is_secret' => 'boolean',
    ];

    public function author()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function tags()
    {
        return $this->morphToMany('App\Models\Tag', 'taggable');
    }

    public function content()
    {
        return $this->morphMany('App\Models\Content', 'contentable');
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
