<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-09
 * Time: 21:51
 */

namespace App\Models;


use App\Services\Relation\Traits\CanBeBookmarked;
use Illuminate\Database\Eloquent\Model;
use App\Services\Relation\Traits\CanBeFollowed;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mews\Purifier\Facades\Purifier;
use Spatie\Permission\Traits\HasRoles;

class Tag extends Model
{
    use CanBeFollowed, CanBeBookmarked, HasRoles, SoftDeletes;

    protected $guard_name = 'api';

    protected $fillable = [
        'slug',
        'deep',
        'redirect_slug',
        'creator_slug',
        'parent_slug',
        'pin_count',            // 文章的数量
        'seen_user_count',      // 看过的人数（可以在这个 tag 下发表文章的人数）（bookmark）
        'followers_count',      // 订阅的人数（可以收到 tag 下文章推送的人数）（follow）
        'activity_stat',        // tag 的活跃度计数
        'migration_state'
    ];

    public function parent()
    {
        return $this->belongsTo('App\Models\Tag', 'parent_slug', 'slug');
    }

    public function creator()
    {
        return $this->belongsTo('App\User', 'id', 'user_id');
    }

    public function children()
    {
        return $this->hasMany('App\Models\Tag', 'parent_slug', 'slug');
    }

    public function rule()
    {
        return $this->hasOne('App\Models\QuestionRule', 'tag_slug', 'slug');
    }

    public function pins()
    {
        return $this->morphedByMany('App\Models\Pin', 'taggable');
    }

    public function content()
    {
        return $this->morphOne('App\Models\Content', 'contentable');
    }

    public function timeline()
    {
        /**
         * 1. 创建
         * 2. 更新
         * 3. 删除
         */
        return $this->morphMany('App\Models\Timeline', 'timelineable');
    }

    public static function createTag($name, $user, $parent)
    {
        $tag = self::create([
            'creator_slug' => $user->slug,
            'parent_slug' => $parent->slug,
            'deep' => $parent->deep + 1
        ]);
        $slug = id2slug($tag->id);
        $tag->update([
            'slug' => $slug
        ]);

        $tag->content()->create([
            'text' => json_encode([
                'name' => $name,
                'alias' => $name,
                'avatar' => '',
                'intro' => ''
            ], JSON_UNESCAPED_UNICODE)
        ]);

        event(new \App\Events\Tag\Create($tag, $user, $parent));

        return $tag;
    }

    public function updateTag(array $data, $user)
    {
        $text = $this
            ->content()
            ->pluck('text')
            ->first();

        $text = json_decode($text, true);
        $newData = array_merge($data, $text);

        foreach ($newData as $key => $val)
        {
            $newData[$key] = trim(Purifier::clean($val));
        }

        $this
            ->content()
            ->create([
                'text' => json_encode($newData, JSON_UNESCAPED_UNICODE)
            ]);

        event(new \App\Events\Tag\Update($this, $user));

        return $this;
    }

    public function deleteTag($user)
    {
        $this->delete();

        event(new \App\Events\Tag\Delete($this, $user));

        return $this;
    }

    public function removePin($pin, $user)
    {
        $this->pins()->detach($pin->id);

        event(new \App\Events\Tag\RemovePin($this, $pin, $user));
    }

    public function addPin(Pin $pin, $user)
    {
        $this->pins()->attach($pin->id);
        event(new \App\Events\Tag\AddPin($this, $pin, $user));
    }
}
