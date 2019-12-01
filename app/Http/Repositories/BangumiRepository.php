<?php


namespace App\Http\Repositories;


use App\Http\Transformers\Bangumi\BangumiItemResource;
use App\Models\Bangumi;

class BangumiRepository extends Repository
{
    public function item($slug, $refresh = false)
    {
        if (!$slug)
        {
            return null;
        }

        $result = $this->RedisItem("bangumi:{$slug}", function () use ($slug)
        {
            $idol = Bangumi
                ::where('slug', $slug)
                ->first();

            if (is_null($idol))
            {
                return 'nil';
            }

            return new BangumiItemResource($idol);
        }, $refresh);

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }

    public function rank($page, $take, $refresh = false)
    {
        $list = $this->RedisSort('bangumi-rank-slug', function ()
        {
            return Bangumi
                ::where('score', '>', 0)
                ->orderBy('score', 'DESC')
                ->orderBy('id', 'DESC')
                ->pluck('score', 'slug')
                ->toArray();

        }, ['force' => $refresh]);

        return $this->filterIdsByPage($list, $page, $take);
    }
}
