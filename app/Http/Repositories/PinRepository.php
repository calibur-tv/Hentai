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

    public function comments($slug, $sort, $count, $specId, $refresh = false)
    {
        if ($refresh)
        {
            $this->hottest_comment($slug, true);
            $this->timeline_comment($slug, $sort, true);
            return [];
        }

        if ($sort === 'hottest')
        {
            $ids = $this->hottest_comment($slug);
            $idsObj = $this->filterIdsBySeenIds($ids, $specId, $count);
        }
        else
        {
            $ids = $this->timeline_comment($slug, $sort);
            $idsObj = $this->filterIdsByMaxId($ids, $specId, $count);
        }

        return $idsObj;
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

    private function hottest_comment($slug, $refresh = false)
    {
        return $this->RedisSort("pin:{$slug}:comments-hottest", function () use ($slug)
        {
            $pin = Pin
                ::where('slug', $slug)
                ->first();

            if (is_null($pin))
            {
                return [];
            }

            $list = $pin
                ->comments()
                ->orderBy('like_count', 'DESC')
                ->orderBy('created_at', 'DESC')
                ->select('like_count', 'created_at', 'id')
                ->get()
                ->toArray();

            $result = [];
            foreach ($list as $row)
            {
                $result[$row['id']] = $row['like_count'] * 10000000000 + strtotime($row['created_at']);
            }

            return $result;
        }, ['force' => $refresh]);
    }

    private function timeline_comment($slug, $sort, $refresh = false)
    {
        $ids = $this->RedisList("pin:{$slug}:comments-{$sort}", function () use ($slug)
        {
            $pin = Pin
                ::where('slug', $slug)
                ->first();

            if (is_null($pin))
            {
                return [];
            }

            return $pin
                ->comments()
                ->orderBy('created_at', 'ASC')
                ->pluck('id')
                ->toArray();
        }, $refresh);

        if ($sort === 'time_desc')
        {
            return array_reverse($ids);
        }

        return $ids;
    }
}
