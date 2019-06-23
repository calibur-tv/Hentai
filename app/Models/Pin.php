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
use App\Services\Trial\WordsFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Relation\Traits\CanBeBookmarked;
use App\Services\Relation\Traits\CanBeFavorited;
use App\Services\Relation\Traits\CanBeVoted;
use Mews\Purifier\Facades\Purifier;
use Spatie\Permission\Traits\HasRoles;

class Pin extends Model
{
    use SoftDeletes, HasRoles,
        CanBeVoted, CanBeBookmarked, CanBeFavorited;

    protected $guard_name = 'api';

    protected $fillable = [
        'slug',
        'title',
        'user_slug',
        'visit_type',       // 访问类型，0 公开，1 私密
        'trial_type',       // 进入审核池的类型，默认 0 不在审核池，1 创建触发敏感词过滤进入审核池
        'delete_type',      // 删除的原因，0 未删除，1，自己删除，2 系统审核删除，3 人工审核删除，4 版主删除
        'comment_type',     // 评论权限的类型
        'content_type',     // 帖子类型
        'last_top_at',      // 最后置顶时间
        'recommended_at',   // 推荐的时间
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'is_secret' => 'boolean',
    ];

    public function setTitleAttribute($title)
    {
        $this->attributes['title'] = Purifier::clean($title);
    }

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
        return $this->morphMany('App\Models\Content', 'contentable');
    }

    public function timeline()
    {
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
        $title = $form['title'];

        $wordsFilter = new WordsFilter();
        $filter = $wordsFilter->check($title);
        if ($filter['delete'])
        {
            return null;
        }

        $richContentService = new RichContentService();
        $risk = $richContentService->detectContentRisk($content, false);

        if ($risk['risk_score'] > 0)
        {
            return null;
        }

        $pin = self::create([
            'user_slug' => $user->slug,
            'title' => $title
        ]);

        $pin->update([
            'slug' => id2slug($pin->id)
        ]);

        $content = $pin->content()->create([
            'text' => $richContentService->saveRichContent($content)
        ]);

        $pin->content = $richContentService->parseRichContent($content->text);

        $pin->tags()->save($form['tag']);

        if ($form['image_count'] > 0)
        {
            $job = (new PinTrial($pin->id, 0));
            dispatch($job);
        }

        return $pin;
    }

    public function deletePin($type)
    {
        $this->update([
            'delete_type' => $type
        ]);
        $this->delete();
        $this->content()->delete();
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
