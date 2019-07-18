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

        $questionCount = $tag
            ->pins()
            ->where('content_type', 2)
            ->count();

        $pinCount = $tag
            ->pins()
            ->where('visit_type', '<>', 1)
            ->where('content_type', '<>', 2)
            ->count();

        return [
            'pin_count' => $pinCount,
            'seen_user_count' => $tag->bookmarkers()->count(),
            'followers_count' => $tag->followers()->count(),
            'question_count' => $questionCount,
            'activity_stat' => $tag->activity_stat
        ];
    }
}
