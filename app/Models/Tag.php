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
use Mews\Purifier\Facades\Purifier;
use Spatie\Permission\Traits\HasRoles;

class Tag extends Model
{
    use CanBeFollowed, CanBeBookmarked, HasRoles;

    protected $guard_name = 'api';

    protected $fillable = [
        'slug',
        'name',
        'avatar',
        'deep',
        'redirect_slug',
        'creator_slug',
        'parent_slug',
        'pin_count',            // 文章的数量
        'seen_user_count',      // 看过的人数（可以在这个 tag 下发表文章的人数）（bookmark）
        'followers_count',      // 订阅的人数（可以收到 tag 下文章推送的人数）（follow）
        'activity_stat',        // tag 的活跃度计数
    ];

    public function setNameAttribute($name)
    {
        $this->attributes['name'] = Purifier::clean($name);
    }

    public function setAvatarAttribute($url)
    {
        $this->attributes['avatar'] = trimImage($url);
    }

    public function getAvatarAttribute($avatar)
    {
        return patchImage($avatar, 'default-poster');
    }

    public function parent()
    {
        return $this->belongsTo('App\Models\Tag', 'parent_slug', 'slug');
    }

    public function creator()
    {
        return $this->belongsTo('App\User', 'id', 'user_id');
    }

    public function fakers()
    {
        return $this->hasMany('App\Models\Tag', 'redirect_slug', 'slug');
    }

    public function children()
    {
        return $this->hasMany('App\Models\Tag', 'parent_slug', 'slug');
    }

    public function pins()
    {
        return $this->morphedByMany('App\Models\Pin', 'taggable');
    }

    public function users()
    {
        return $this->morphedByMany('App\Models\Pin', 'taggable');
    }

    public function extra()
    {
        return $this->morphOne('App\Models\Content', 'contentable');
    }

    public function timeline()
    {
        return $this->morphMany('App\Models\Timeline', 'timelineable');
    }

    public static function createTag(array $data, array $extra, $user)
    {
        $tag = self::create($data);
        $slug = id2slug($tag->id);
        $tag->update([
            'slug' => $slug
        ]);
        $tag->extra()->create([
            'text' => json_encode($extra, JSON_UNESCAPED_UNICODE)
        ]);

        event(new \App\Events\Tag\Create($tag, $user));

        return $tag;
    }

    public function updateTag(array $data, array $extra)
    {
        $this->update($data);

        $text = $this->extra()->pluck('text');
        $text = json_decode($text, true);
        $newData = array_merge($extra, $text);
        foreach ($newData as $key => $val)
        {
            $newData[$key] = Purifier::clean($val);
        }
        $this->extra()->update([
            'text' => json_encode($newData, JSON_UNESCAPED_UNICODE)
        ]);

        return $this;
    }

    public function deleteTag()
    {
        $this->delete();

        $this->extra()->delete();

        return $this;
    }
}
