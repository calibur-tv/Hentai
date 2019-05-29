<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:31
 */

namespace App\Http\Repositorys\v1;


use App\Http\Repositories\Repository;
use App\User;

class UserRepository extends Repository
{
    public function item($slug)
    {
        $result = $this->Cache($this->tag_cache_key($slug), function () use ($slug)
        {
            $user = User
                ::where('slug', $slug)
                ->first();

            if (is_null($user))
            {
                return 'nil';
            }

            return $user;
        });

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }

    public function tag_cache_key($slug)
    {
        return "user-{$slug}";
    }
}
