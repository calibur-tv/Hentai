<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:31
 */

namespace App\Http\Repositories;


use App\Http\Transformers\User\UserHomeResource;
use App\User;

class UserRepository extends Repository
{
    public function item($slug, $refresh = false)
    {
        if (!$slug)
        {
            return null;
        }

        $result = $this->RedisItem("user:{$slug}", function () use ($slug)
        {
            $user = User
                ::where('slug', $slug)
                ->first();

            if (is_null($user))
            {
                return 'nil';
            }

            return new UserHomeResource($user);
        }, $refresh);

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }

    public function followers($slug, $refresh = false, $seenIds = [], $count = 15)
    {
        // 动态有序要分页
        $ids = $this->RedisSort($this->followers_cache_key($slug), function () use ($slug)
        {
            $user = User
                ::where('slug', $slug)
                ->first();

            if (is_null($user))
            {
                return [];
            }

            return $user
                ->followers()
                ->orderBy('created_at', 'DESC')
                ->pluck('created_at', 'slug')
                ->toArray();

        }, ['force' => $refresh, 'is_time' => true]);

        return $this->filterIdsBySeenIds($ids, $seenIds, $count);
    }

    public function followings($slug, $refresh = false)
    {
        // 动态有序不分页
        $ids = $this->RedisList($this->followings_cache_key($slug), function () use ($slug)
        {
            $user = User
                ::where('slug', $slug)
                ->first();

            if (is_null($user))
            {
                return [];
            }

            return $user
                ->followings()
                ->orderBy('created_at', 'DESC')
                ->pluck('slug')
                ->toArray();
        }, $refresh);

        return [
            'result' => $ids,
            'total' => count($ids),
            'no_more' => true
        ];
    }

    public function friends($slug, $refresh = false)
    {
        // 动态有序不分页
        $ids = $this->RedisList($this->friends_cache_key($slug), function () use ($slug)
        {
            $user = User
                ::where('slug', $slug)
                ->first();

            if (is_null($user))
            {
                return [];
            }

            $userFollowers = $this->followers($slug, false, [], 99999999);
            $userFollowings = $this->followings($slug, false);

            return array_intersect($userFollowers['result'], $userFollowings['result']);
        }, $refresh);

        return [
            'result' => $ids,
            'total' => count($ids),
            'no_more' => true
        ];
    }

    public function timeline($slug, $refresh = false, $page = 0, $count = 10)
    {
        $list = $this->RedisSort("user-{$slug}-timeline", function () use ($slug)
        {
            $user = User
                ::where('slug', $slug)
                ->first();

            if (is_null($user))
            {
                return [];
            }

            $list = $user
                ->timeline()
                ->select('event_type', 'event_slug', 'created_at')
                ->orderBy('created_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->get()
                ->toArray();

            $result = [];
            foreach ($list as $row)
            {
                $result["{$row['event_type']}#{$row['event_slug']}"] = $row['created_at'];
            }

            return $result;
        }, ['force' => $refresh, 'is_time' => true, 'with_score' => true]);

        if ($refresh)
        {
            return [];
        }

        $idsObj = $this->filterIdsByPage($list, $page, $count, true);
        $result = [];

        foreach ($idsObj['result'] as $key => $val)
        {
            $event = explode('#', $key);
            $result[] = [
                'type' => $event[0],
                'slug' => $event[1],
                'created_at' => $val
            ];
        }

        return [
            'result' => $result,
            'total' => $idsObj['total'],
            'no_more' => $idsObj['no_more']
        ];
    }

    public function followers_cache_key($slug)
    {
        return "user-followers:{$slug}";
    }

    public function followings_cache_key($slug)
    {
        return "user-followings:{$slug}";
    }

    public function friends_cache_key($slug)
    {
        return "user-friends:{$slug}";
    }
}
