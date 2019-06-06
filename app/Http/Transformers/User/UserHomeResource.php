<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers\User;

use App\Http\Modules\DailyRecord\UserDailySign;
use App\Http\Repositorys\v1\UserRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class UserHomeResource extends JsonResource
{
    public function toArray($request)
    {
        $userDailySign = new UserDailySign();
        $userRepository = new UserRepository();

        return [
            'slug' => $this->slug,
            'nickname' => $this->nickname,
            'avatar' => $this->avatar,
            'banner' => $this->banner,
            'level' => $this->level,
            'birthday' => $this->birth_secret ? -1 : $this->birthday,
            'daily_signed' => $userDailySign->check($this->id),
            'sex' => $this->sex_secret ? -1 : $this->sex,
            'signature' => $this->signature,
            'roles' => $userRepository->userRoleNames($this)
        ];
    }
}
