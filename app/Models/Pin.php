<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-09
 * Time: 21:51
 */

namespace App\Models;


use App\Http\Modules\RichContentService;
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
        'user_id',
        'trial_type',       // 进入审核池的类型，默认 0 不在审核池
        'comment_type',     // 评论权限的类型
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

        // TODO：进入图片审核队列

        return $pin;
    }
}
