<?php


namespace App\Http\Modules\Counter;


use App\Models\Pin;

class PinPatchCounter extends HashCounter
{
    public function __construct()
    {
        parent::__construct('pins', [
            'visit_count',
            'comment_count',
            'like_count',
            'mark_count',
            'reward_count'
        ]);
    }

    public function boot($slug)
    {
        $pin = Pin
            ::where('slug', $slug)
            ->first();

        return [
            'visit_count' => $pin->visit_count,
            'comment_count' => $pin->comments()->count(),
            'mark_count' => $pin
                ->tags()
                ->whereNotIn('parent_slug', [
                    config('app.tag.bangumi'),
                    config('app.tag.game'),
                    config('app.tag.topic')
                ])
                ->count() - 1, // 自己的专栏要减1
            'reward_count' => $pin->favoriters()->count(),
            'like_count' => $pin->upvoters()->count()
        ];
    }
}
