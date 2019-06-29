<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:31
 */

namespace App\Http\Repositories;


use App\Http\Transformers\PinResource;
use App\Models\Pin;

class PinRepository extends Repository
{
    public function item($slug, $refresh = false)
    {
        $result = $this->RedisItem("pin:{$slug}", function () use ($slug)
        {
            $pin = Pin
                ::withTrashed()
                ->with('author')
                ->where('slug', $slug)
                ->first();

            if (is_null($pin))
            {
                return 'nil';
            }

            $pin->content = $pin
                ->content()
                ->latest()
                ->pluck('text')
                ->first();

            $pin->notebook = $pin
                ->tags()
                ->where('parent_slug', config('app.tag.notebook'))
                ->first();

            $pin->area = $pin
                ->tags()
                ->whereIn('parent_slug', [
                    config('app.tag.bangumi'),
                    config('app.tag.topic'),
                    config('app.tag.game')
                ])
                ->first();

            $pin->tags = $pin
                ->tags()
                ->where('parent_slug', config('app.tag.pin'))
                ->get();

            return new PinResource($pin);
        }, $refresh);

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }

    public function drafts($slug, $page, $take, $refresh = false)
    {
        $ids = $this->RedisSort("user-{$slug}-drafts", function () use ($slug)
        {
            return Pin
                ::where('user_slug', $slug)
                ->where('visit_type', 1)
                ->orderBy('last_edit_at', 'DESC')
                ->pluck('last_edit_at', 'slug')
                ->toArray();
        }, ['force' => $refresh, 'is_time' => true]);

        return $this->filterIdsByPage($ids, $page, $take, true);
    }
}
