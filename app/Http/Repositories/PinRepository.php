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
                ->get();

            $pin->tags = $pin
                ->tags()
                ->where('parent_slug', config('app.tag.pin'))
                ->get();

            $pin->area = $pin
                ->tags()
                ->whereIn('parent_slug', [
                    config('app.tag.bangumi'),
                    config('app.tag.topic'),
                    config('app.tag.game')
                ])
                ->get();

            return new PinResource($pin);
        }, $refresh);

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }
}
