<?php


namespace App\Http\Repositories;


use App\Http\Transformers\Idol\IdolItemResource;
use App\Models\IdolExtra;
use App\Models\Tag;

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
            $idol = Tag
                ::where('slug', $slug)
                ->with(['extra', 'content' => function ($query)
                {
                    $query->orderBy('created_at', 'desc');
                }])
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
        $list = $this->RedisList($this->idolIdsCacheKey('hot'), function ()
        {
            $keyVal = IdolExtra
                ::orderBy('market_price', 'DESC')
                ->orderBy('fans_count', 'DESC')
                ->pluck('idol_slug', 'lover_user_slug')
                ->toArray();

            $result = [];
            foreach ($keyVal as $user => $slug)
            {
                $result[] = $slug . '#' . $user;
            }

            return $result;

        }, $refresh);

        return $this->filterIdsByPage($list, $page, $take);
    }

    protected function idolIdsCacheKey($sort)
    {
        return "idol-list-{$sort}-ids";
    }
}
