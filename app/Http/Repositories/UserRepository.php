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

    public function fans($slug, $refresh = false, $seenIds = [], $count = 15)
    {
        // 动态有序要分页
        $ids = $this->RedisSort("user-followings:{$slug}", function () use ($slug)
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

        return $this->filterIdsBySeenIds($ids, $seenIds, $count);
    }

    public function followings($slug, $refresh = false)
    {
        // 动态有序不分页
        return $this->RedisList("user-followings:{$slug}", function () use ($slug)
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
    }

    public function friends($slug, $refresh = false)
    {
        // 动态有序不分页
        return $this->RedisList("user-friends:{$slug}", function () use ($slug)
        {
            $user = User
                ::where('slug', $slug)
                ->first();

            if (is_null($user))
            {
                return [];
            }

            $userFollowers = $this->fans($slug);
            $userFollowings = $this->followings($slug);

            return array_intersect($userFollowers, $userFollowings);
        }, $refresh);
    }
}
