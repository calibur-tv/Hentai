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
        if (!$slug)
        {
            return null;
        }

        $result = $this->RedisItem("pin:{$slug}", function () use ($slug)
        {
            $pin = Pin
                ::withTrashed()
                ->with(['author', 'content' => function ($query)
                {
                    $query->orderBy('created_at', 'desc');
                }])
                ->where('slug', $slug)
                ->first();

            if (is_null($pin))
            {
                return 'nil';
            }

            if ($pin->main_notebook_slug)
            {
                $pin->notebook = $pin
                    ->tags()
                    ->where('slug', $pin->main_notebook_slug)
                    ->with(
                        [
                            'content' => function ($query)
                            {
                                $query->orderBy('created_at', 'desc');
                            }
                        ]
                    )
                    ->first();
            }

            if ($pin->main_area_slug)
            {
                $pin->area = $pin
                    ->tags()
                    ->where('slug', $pin->main_area_slug)
                    ->with(
                        [
                            'content' => function ($query)
                            {
                                $query->orderBy('created_at', 'desc');
                            }
                        ]
                    )
                    ->first();
            }

            if ($pin->main_topic_slug)
            {
                $pin->topic = $pin
                    ->tags()
                    ->where('slug', $pin->main_topic_slug)
                    ->with(
                        [
                            'content' => function ($query)
                            {
                                $query->orderBy('created_at', 'desc');
                            }
                        ]
                    )
                    ->first();
            }

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
                ->whereNull('published_at')
                ->orderBy('last_edit_at', 'DESC')
                ->pluck('last_edit_at', 'slug')
                ->toArray();
        }, ['force' => $refresh, 'is_time' => true]);

        return $this->filterIdsByPage($ids, $page, $take, true);
    }

    public function decrypt($request)
    {
        $key = $request->get('key');
        $ts = $request->get('ts');
        if (!$key || !$ts)
        {
            return '该文章尚未发布';
        }

        if ($key !== md5(config('app.md5') . $request->get('slug') . $ts))
        {
            return '密码不正确';
        }

        if (abs(time() - $ts) > 300)
        {
            return '密码已过期';
        }

        return '';
    }

    public function encrypt($slug)
    {
        $ts = time();
        return $slug . '?key=' . (md5(config('app.md5') . $slug . $ts)) . '&ts=' . $ts;
    }
}
