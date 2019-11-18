<?php


namespace App\Http\Repositories;


use App\Http\Transformers\Idol\IdolItemResource;
use App\Models\Idol;

class IdolRepository extends Repository
{
    public function item($slug, $refresh = false)
    {
        if (!$slug)
        {
            return null;
        }

        $result = $this->RedisItem("idol:{$slug}", function () use ($slug)
        {
            $idol = Idol
                ::where('slug', $slug)
                ->first();

            if (is_null($idol))
            {
                return 'nil';
            }

            return new IdolItemResource($idol);
        }, $refresh);

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }

    public function idolHotIds($page, $take, $refresh = false)
    {
        $list = $this->RedisSort($this->idolIdsCacheKey('topped'), function ()
        {
            return Idol
                ::orderBy('market_price', 'DESC')
                ->orderBy('stock_price', 'DESC')
                ->pluck('market_price', 'slug')
                ->toArray();

        }, ['force' => $refresh]);

        return $this->filterIdsByPage($list, $page, $take);
    }

    public function idolReleaseIds($page, $take, $refresh = false)
    {
        $list = $this->RedisSort($this->idolIdsCacheKey('newbie'), function ()
        {
            return Idol
                ::where('is_newbie', 1)
                ->orderBy('market_price', 'DESC')
                ->orderBy('stock_price', 'DESC')
                ->pluck('market_price', 'slug')
                ->toArray();

        }, ['force' => $refresh]);

        return $this->filterIdsByPage($list, $page, $take);
    }

    public function idolActiveIds($page, $take, $refresh = false)
    {
        $list = $this->RedisSort($this->idolIdsCacheKey('activity'), function ()
        {
            return Idol
                ::orderBy('updated_at', 'DESC')
                ->pluck('updated_at', 'slug')
                ->toArray();

        }, ['force' => $refresh, 'is_time' => true]);

        return $this->filterIdsByPage($list, $page, $take);
    }

    protected function idolIdsCacheKey($sort)
    {
        return "idol-list-{$sort}-ids";
    }
}
