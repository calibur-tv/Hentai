<?php


namespace App\Http\Modules\Counter;


use App\Models\Bangumi;
use App\Models\Search;

class BangumiPatchCounter extends HashCounter
{
    public function __construct()
    {
        parent::__construct('bangumis');
    }

    public function boot($slug)
    {
        $bangumi = Bangumi
            ::where('slug', $slug)
            ->first();

        if (is_null($bangumi))
        {
            return [
                'subscribe_user_count' => 0,
                'like_user_count' => 0
            ];
        }

        return [
            'subscribe_user_count' => $bangumi->subscribe_user_count,
            'like_user_count' => $bangumi->like_user_count
        ];
    }

    public function search($slug, $result)
    {
        Search
            ::where('slug', $slug)
            ->where('type', 1)
            ->update([
                'score' =>
                    $result['subscribe_user_count'] +
                    $result['like_user_count']
            ]);
    }
}
