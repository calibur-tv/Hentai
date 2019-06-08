<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:31
 */

namespace App\Http\Repositories;


use App\Http\Transformers\User\UserHomeResource;
use App\Models\MessageMenu;
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

    public function messageMenu($slug)
    {
        $cacheKey = MessageMenu::cacheKey($slug);
        $cache = $this->RedisSort($cacheKey, function () use ($slug)
        {
            $menus = MessageMenu
                ::where('to_user_slug', $slug)
                ->orWhere('from_user_slug', $slug)
                ->orderBy('updated_at', 'DESC')
                ->get()
                ->toArray();

            $result = [];
            foreach ($menus as $menu)
            {
                $isMine = $menu['from_user_slug'] === $slug;
                $slug = $isMine ? $menu['to_user_slug'] : $menu['from_user_slug'];
                if ($isMine) {
                    $msgCount = '000';
                } else if (intval($menu['count']) > 999) {
                    $msgCount = '999';
                } else {
                    $msgCount = str_pad($menu['count'], 3, '0', STR_PAD_LEFT);
                }
                $key = $menu['type'] . '#' . $slug;
                $val = strtotime($menu['updated_at']) . $msgCount;
                $result[$key] = $val;
            }

            return $result;
        }, ['with_score' => true]);

        if (empty($cache))
        {
            return [];
        }

        $result = [];
        foreach ($cache as $key => $value)
        {
            $arr1 = explode('#', $key);
            $result[] = [
                'channel' => $key,
                'type' => $arr1[0],
                'slug' => $arr1[1],
                'time' => substr($value, 0, -3),
                'count' => intval(substr($value, -3))
            ];
        }

        return $result;
    }
}
