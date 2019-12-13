<?php


namespace App\Models;


use App\Services\Relation\Traits\CanBeLiked;
use App\Services\Relation\Traits\CanBeSubscribed;
use Illuminate\Database\Eloquent\Model;

class Bangumi extends Model
{
    use CanBeLiked, CanBeSubscribed;

    protected $table = 'bangumis';

    protected $fillable = [
        'slug',
        'title',
        'alias',
        'intro',
        'avatar',
        'source_id',
        'parent_slug',
        'is_parent',
        'migration_state',
        'rank',
        'score'
    ];

    protected $casts = [
        'is_parent' => 'boolean'
    ];

    public function setAvatarAttribute($url)
    {
        $this->attributes['avatar'] = trimImage($url);
    }

    public function getAvatarAttribute($avatar)
    {
        return patchImage($avatar, 'default-avatar');
    }
}
