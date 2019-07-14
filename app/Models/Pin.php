<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-09
 * Time: 21:51
 */

namespace App\Models;


use App\Http\Modules\RichContentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Relation\Traits\CanBeBookmarked;
use App\Services\Relation\Traits\CanBeFavorited;
use App\Services\Relation\Traits\CanBeVoted;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

class Pin extends Model
{
    use SoftDeletes, HasRoles,
        CanBeVoted, CanBeBookmarked, CanBeFavorited;

    protected $guard_name = 'api';

    protected $fillable = [
        'slug',
        'user_slug',
        'visit_type',       // 访问类型，0 已发布，1 草稿箱, 2 仅好友可见
        'trial_type',       // 进入审核池的类型，默认 0 不在审核池，1 创建触发敏感词过滤进入审核池
        'comment_type',     // 评论权限的类型，默认 0 允许所有人评论
        'content_type',     // 内容类型：0 普通图文贴，1 专栏
        'last_top_at',      // 最后置顶时间
        'last_edit_at',     // 最后编辑时间
        'published_at',     // 发布时间
        'recommended_at',   // 推荐的时间
        'visit_count',      // 访问数
        'comment_count',    // 评论数
        'like_count',       // 点赞数
        'mark_count',       // 收藏数
        'reward_count',     // 打赏数
    ];

    public function author()
    {
        return $this->belongsTo('App\User', 'user_slug', 'slug');
    }

    public function tags()
    {
        return $this->morphToMany('App\Models\Tag', 'taggable');
    }

    public function content()
    {
        return $this->morphOne('App\Models\Content', 'contentable');
    }

    public function history()
    {
        return $this->morphMany('App\Models\Content', 'contentable');
    }

    public function timeline()
    {
        /**
         * 0 => 创建帖子
         * 1 => 更新帖子
         * 2 => 作者删除
         * 3 => 公开帖子
         */
        return $this->morphMany('App\Models\Timeline', 'timelineable');
    }

    public function comments()
    {
        return $this->hasMany('App\Models\Comment', 'pin_slug', 'slug');
    }

    public function reports()
    {
        return $this->morphMany('App\Models\Report', 'reportable');
    }

    public static function createPin($content, $content_type, $visit_type, $user, $tags)
    {
        $richContentService = new RichContentService();
        $risk = $richContentService->detectContentRisk($content, false);

        if ($risk['risk_score'] > 0)
        {
            return null;
        }

        $now = Carbon::now();
        $data = [
            'user_slug' => $user->slug,
            'content_type' => $content_type,
            'visit_type' => $visit_type,
            'last_edit_at' => $now
        ];
        if ($visit_type == 0)
        {
            $data['published_at'] = $now;
        }

        $pin = self::create($data);

        $pin->update([
            'slug' => id2slug($pin->id)
        ]);

        $richContent = $pin->content()->create([
            'text' => $richContentService->saveRichContent($content)
        ]);
        $pin->content = $richContent->text;

        event(new \App\Events\Pin\Create($pin, $user, $tags, $visit_type == 0));

        return $pin;
    }

    public function updatePin($content, $visit_type, $user, $tags)
    {
        $richContentService = new RichContentService();
        $risk = $richContentService->detectContentRisk($content, false);

        if ($risk['risk_score'] > 0)
        {
            return false;
        }

        $doPublish = $this->visit_type === 0 && $visit_type !== 0;
        $now = Carbon::now();
        $data = [
            'last_edit_at' => $now,
            'visit_type' => $visit_type
        ];
        if ($doPublish)
        {
            $data['published_at'] = $now;
        }

        $this->update($data);

        $richContent = $this->content()->create([
            'text' => $richContentService->saveRichContent($content)
        ]);
        $this->content = $richContent->text;

        event(new \App\Events\Pin\Update($this, $user, $tags, $doPublish));

        return true;
    }

    public function deletePin($user)
    {
        $this->delete();

        event(new \App\Events\Pin\Delete($this, $user));
    }

    public function reviewPin($type)
    {
        // 进入审核
        $this->update([
            'trial_type' => $type
        ]);
    }
}
