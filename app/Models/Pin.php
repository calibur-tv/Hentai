<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-09
 * Time: 21:51
 */

namespace App\Models;


use App\Http\Modules\RichContentService;
use App\Jobs\Trial\PinTrial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Relation\Traits\CanBeBookmarked;
use App\Services\Relation\Traits\CanBeFavorited;
use App\Services\Relation\Traits\CanBeVoted;
use Spatie\Permission\Traits\HasRoles;

class Pin extends Model
{
    use SoftDeletes, HasRoles,
        CanBeVoted, CanBeBookmarked, CanBeFavorited;

    protected $guard_name = 'api';

    protected $fillable = [
        'slug',
        'user_slug',
        'visit_type',       // 访问类型，0 公开，1 私密
        'trial_type',       // 进入审核池的类型，默认 0 不在审核池，1 创建触发敏感词过滤进入审核池
        'comment_type',     // 评论权限的类型
        'content_type',     // 内容类型：0 普通图文贴，1 专栏
        'last_top_at',      // 最后置顶时间
        'recommended_at',   // 推荐的时间
        'image_count',      // 图片数
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

    public static function createPin($form, $user)
    {
        $content = $form['content'];

        $richContentService = new RichContentService();
        $risk = $richContentService->detectContentRisk($content, false);

        if ($risk['risk_score'] > 0)
        {
            return null;
        }

        $pin = self::create([
            'user_slug' => $user->slug,
            'content_type' => $form['content_type'],
            'image_count' => $form['image_count']
        ]);

        $pin->update([
            'slug' => id2slug($pin->id)
        ]);

        $content = $pin->content()->create([
            'text' => $richContentService->saveRichContent($content)
        ]);

        $pin->content = $richContentService->parseRichContent($content->text);

        $pin->tags()->save($form['tag']);

        $pin->timeline()->create([
            'event_type' => 0,
            'event_slug' => $user->slug
        ]);

        if ($form['image_count'] > 0)
        {
            $job = (new PinTrial($pin->id, 0));
            dispatch($job);
        }

        return $pin;
    }

    public function deletePin()
    {
        $this->delete();
        $this->content()->delete();
        $this->tags()->delete();
    }

    public function reviewPin($type)
    {
        // 进入审核
        $this->update([
            'trial_type' => $type
        ]);
    }

    public function reflowPin()
    {
        // 进入信息流
    }
}
