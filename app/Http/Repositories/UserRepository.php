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

    public function fans($slug, $page = -1, $count = 15, $refresh = false)
    {
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

        if (-1 === $page)
        {
            return $ids;
        }

        return $this->filterIdsByPage($ids, $page, $count);
    }

    public function followings($slug, $refresh = false)
    {
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
