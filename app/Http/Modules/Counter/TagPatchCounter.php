<?php


namespace App\Http\Modules\Counter;


use App\Models\Tag;

class TagPatchCounter extends HashCounter
{
    public function __construct()
    {
        parent::__construct('tags', [
            'pin_count',
            'seen_user_count',
            'followers_count',
            'activity_stat',
        ]);
    }

    public function boot($slug)
    {
        $tag = Tag
            ::where('slug', $slug)
            ->first();

        return [
            'pin_count' => $tag->pins()->where('visit_type', '<>', 1)->count(),
            'seen_user_count' => $tag->bookmarks()->count(),
            'followers_count' => $tag->followings()->count(),
            'activity_stat' => $tag->activity_stat
        ];
    }
}
