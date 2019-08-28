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
        'trial_type',       // 进入审核池的类型，默认 0 不在审核池，1 创建触发敏感词过滤进入审核池
        'comment_type',     // 评论权限的类型，默认 0 允许所有人评论
        'content_type',     // 内容类型：1 是 calibur 公开帖子，2 是 calibur 分区答题
        'last_top_at',      // 最后置顶时间
        'last_edit_at',     // 最后编辑时间
        'published_at',     // 发布时间
        'recommended_at',   // 推荐的时间
        'visit_count',      // 访问数
        'comment_count',    // 评论数
        'like_count',       // 点赞数
        'mark_count',       // 收藏数
        'reward_count',     // 打赏数
        'main_area_slug',
        'main_topic_slug',
        'main_notebook_slug',
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
         * 2 => 删除帖子
         * 3 => 公开帖子
         * 4 => 被推荐
         * 5 => 移动帖子
         */
        return $this->morphMany('App\Models\Timeline', 'timelineable');
    }

    public static function convertTimeline($event_type)
    {
        if ($event_type == 0) {
            return '创建帖子';
        } else if ($event_type == 1) {
            return '更新帖子';
        } else if ($event_type == 2) {
            return '删除帖子';
        } else if ($event_type == 3) {
            return '公开帖子';
        } else if ($event_type == 4) {
            return '被推荐';
        } else if ($event_type == 5) {
            return '修改分区';
        }
        return '未知：' . $event_type;
    }

    public function comments()
    {
        return $this->hasMany('App\Models\Comment', 'pin_slug', 'slug');
    }

    public function answers()
    {
        return $this->hasMany('App\Models\PinAnswer', 'pin_slug', 'slug');
    }

    public function reports()
    {
        return $this->morphMany('App\Models\Report', 'reportable');
    }

    public static function createPin(
        $content,
        $content_type,
        $publish,
        $user,
        $main_area_slug = '',
        $main_topic_slug = '',
        $main_notebook_slug = ''
    )
    {
        $richContentService = new RichContentService();
        $content = $richContentService->preFormatContent($content);
        $risk = $richContentService->detectContentRisk($content, false);

        if ($risk['risk_score'] > 0)
        {
            return null;
        }

        $now = Carbon::now();
        $data = [
            'user_slug' => $user->slug,
            'content_type' => $content_type,
            'last_edit_at' => $now,
            'main_area_slug' => $main_area_slug,
            'main_topic_slug' => $main_topic_slug,
            'main_notebook_slug' => $main_notebook_slug
        ];
        if ($publish)
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
        $tags = [$main_area_slug, $main_topic_slug, $main_notebook_slug];

        event(new \App\Events\Pin\Create($pin, $user, $tags, $publish));

        return $pin;
    }

    public function updatePin(
        $content,
        $publish,
        $user,
        $main_area_slug = '',
        $main_topic_slug = '',
        $main_notebook_slug = ''
    )
    {
        $richContentService = new RichContentService();
        if (!$this->published_at)
        {
            // 还未公开发布的文章
            $content = $richContentService->preFormatContent($content);
        }
        else
        {
            // 已发布的文章
            // 不能编辑投票
            $newVote = $richContentService->getFirstType($content, 'vote');
            if ($newVote)
            {
                $lastContent = $this
                    ->content()
                    ->orderBy('created_at', 'desc')
                    ->pluck('text')
                    ->first();
                $oldVote = $richContentService->getFirstType($lastContent, 'vote');

                if ($oldVote)
                {
                    foreach ($content as $i => $row)
                    {
                        if ($row['type'] === 'vote')
                        {
                            $content[$i] = [
                                'type' => 'vote',
                                'data' => $oldVote
                            ];
                        }
                    }
                }
            }
        }
        $risk = $richContentService->detectContentRisk($content, false);

        if ($risk['risk_score'] > 0)
        {
            return false;
        }

        $doPublish = !$this->published_at && $publish;
        $now = Carbon::now();
        $data = [
            'last_edit_at' => $now,
            'main_area_slug' => $main_area_slug,
            'main_topic_slug' => $main_topic_slug,
            'main_notebook_slug' => $main_notebook_slug
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
        $tags = [$main_area_slug, $main_topic_slug, $main_notebook_slug];

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
