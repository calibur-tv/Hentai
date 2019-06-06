<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers\User;

use App\Http\Repositorys\v1\UserRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class UserItemResource extends JsonResource
{
    public function toArray($request)
    {
        $userRepository = new UserRepository();

        return [
            'slug' => $this->slug,
            'nickname' => $this->nickname,
            'avatar' => $this->avatar,
            'roles' => $userRepository->userRoleNames($this),
            'level' => $this->level
        ];
    }
}
