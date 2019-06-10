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

    public function list($ids)
    {
        if (empty($ids))
        {
            return [];
        }

        $result = [];
        foreach ($ids as $id)
        {
            $item = $this->item($id);
            if ($item)
            {
                $result[] = $item;
            }
        }
        return $result;
    }

    public function followers($slug, $refresh = false, $seenIds = [], $count = 15)
    {
        // 动态有序要分页
        $ids = $this->RedisSort("user-followers:{$slug}", function () use ($slug)
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
                ->pluck('created_at', 'slug')
                ->toArray();

        }, ['force' => $refresh, 'is_time' => true]);

        if ($refresh)
        {
            return $ids;
        }

        return $this->filterIdsBySeenIds($ids, $seenIds, $count);
    }

    public function followings($slug, $refresh = false)
    {
        // 动态有序不分页
        $ids = $this->RedisList("user-followings:{$slug}", function () use ($slug)
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
        $ids = $this->RedisList("user-friends:{$slug}", function () use ($slug)
        {
            $user = User
                ::where('slug', $slug)
                ->first();

            if (is_null($user))
            {
                return [];
            }

            $userFollowers = $this->followers($slug, true);
            $userFollowings = $this->followings($slug);

            return array_intersect($userFollowers, $userFollowings);
        }, $refresh);

        return [
            'result' => $ids,
            'total' => count($ids),
            'no_more' => true
        ];
    }
}
