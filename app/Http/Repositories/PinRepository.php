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

            $pin->notebook = $pin
                ->tags()
                ->where('parent_slug', config('app.tag.notebook'))
                ->orderBy('id', 'ASC')
                ->first();

            $pin->area = $pin
                ->tags()
                ->whereIn('parent_slug', [
                    config('app.tag.bangumi'),
                    config('app.tag.topic'),
                    config('app.tag.game')
                ])
                ->orderBy('id', 'ASC')
                ->first();

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
