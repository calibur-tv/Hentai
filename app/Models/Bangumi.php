<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Bangumi extends Model
{
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
