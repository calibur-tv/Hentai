<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:31
 */

namespace App\Http\Repositorys\v1;


use App\Http\Repositories\Repository;
use App\Http\Transformers\PinResource;
use App\Http\Transformers\Tag\TagItemResource;
use App\Http\Transformers\User\UserItemResource;
use App\Models\Pin;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;

class PinRepository extends Repository
{
    public function item($slug)
    {
        $result = $this->Cache($this->item_cache_key($slug), function () use ($slug)
        {
            $pin = Pin
                ::withTrashed()
                ->where('slug', $slug)
                ->first();

            if (is_null($pin))
            {
                return 'nil';
            }

            $tempUser = $pin
                ->author()
                ->first();

            if (is_null($tempUser))
            {
                return 'nil';
            }

            $pin->author = new UserItemResource($tempUser);

            $tagTemp = $pin
                ->tags()
                ->select(DB::raw('count(*) as count, tag_id'))
                ->groupBy('tag_id')
                ->orderBy('count', 'DESC')
                ->get()
                ->toArray();

            $tags = [];
            if (count($tagTemp))
            {
                $tagIds = array_map(function ($item)
                {
                    return $item['tag_id'];
                }, $tagTemp);

                $tagIdsStr = implode(',', $tagIds);

                $tags = Tag
                    ::whereIn('id', $tagIds)
                    ->orderByRaw("FIELD(id, $tagIdsStr)")
                    ->get();

                foreach ($tagTemp as $i => $item)
                {
                    $tags[$i]['count'] = $item['count'];
                }

                $tags = TagItemResource::collection($tags);
            }

            $pin->tags = $tags;

            return new PinResource($pin);
        }, 'd', true);

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }

    public function item_cache_key($slug)
    {
        return "pin_{$slug}";
    }
}
