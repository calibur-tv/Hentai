<?php


namespace App\Http\Repositories;


use App\Http\Transformers\Bangumi\BangumiItemResource;
use App\Models\Bangumi;
use App\Models\BangumiQuestionRule;
use App\Models\Idol;

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

    public function idol_slugs($slug, $page, $take, $refresh = false)
    {
        $list = $this->RedisSort($this->bangumiIdolsCacheKey($slug), function () use ($slug)
        {
            return Idol
                ::where('bangumi_slug', $slug)
                ->orderBy('market_price', 'DESC')
                ->orderBy('stock_price', 'DESC')
                ->pluck('market_price', 'slug')
                ->toArray();

        }, ['force' => $refresh]);

        return $this->filterIdsByPage($list, $page, $take);
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

    public function rule($slug, $refresh = false)
    {
        return $this->RedisItem("bangumi-join-rule:{$slug}", function () use ($slug)
        {
            return BangumiQuestionRule
                ::where('tag_slug', $slug)
                ->first();
        }, $refresh);
    }

    public function bangumiIdolsCacheKey($slug)
    {
        return "bangumi-{$slug}-idol-slug";
    }
}
