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
use App\Services\Relation\Traits\CanFavorite;
use Mews\Purifier\Facades\Purifier;
use Spatie\Permission\Traits\HasRoles;

class Tag extends Model
{
    use CanBeFollowed, CanBeBookmarked, CanFavorite, HasRoles;

    protected $guard_name = 'api';

    protected $fillable = [
        'slug',
        'name',
        'avatar',
        'deep',
        'redirect_slug',
        'creator_id',
        'parent_slug',
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

    public static function createTag(array $data, array $extra)
    {
        $tag = self::create($data);
        $slug = id2slug($tag->id);
        $tag->update([
            'slug' => $slug
        ]);
        $tag->extra()->create([
            'text' => json_encode($extra, JSON_UNESCAPED_UNICODE)
        ]);

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
