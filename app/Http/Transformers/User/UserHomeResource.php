<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers\User;

use App\Http\Modules\Counter\UserBeFollowedCounter;
use App\Http\Modules\Counter\UserFollowingCounter;
use App\Http\Modules\DailyRecord\UserDailySign;
use Illuminate\Http\Resources\Json\JsonResource;

class UserHomeResource extends JsonResource
{
    public function toArray($request)
    {
        $userDailySign = new UserDailySign();
        $userBeFollowedCounter = new UserBeFollowedCounter();
        $userFollowingCounter = new UserFollowingCounter();

        return [
            'slug' => $this->slug,
            'nickname' => $this->nickname,
            'avatar' => $this->avatar,
            'banner' => $this->banner,
            'signature' => $this->signature,
            'title' => $this->title,
            'level' => $this->level,
            'sex' => $this->sex_secret ? -1 : $this->sex,
            'birthday' => $this->birth_secret ? -1 : $this->birthday,
            'social' => [
                'followers_count' => $userBeFollowedCounter->get($this->slug),
                'following_count' => $userFollowingCounter->get($this->slug),
                'relation' => 'unknown'
            ],
            'balance' => [
                'coin' => sprintf("%.2f", $this->virtual_coin),
                'money' => sprintf("%.2f", $this->money_coin),
            ],
            'sign' => [
                'daily_signed' => $userDailySign->check($this->id),
                'continuous_sign_count' => $this->continuous_sign_count,
                'total_sign_count' => $this->total_sign_count,
                'latest_signed_at' => $this->latest_signed_at,
            ],
            'stat' => [
                'activity' => $this->activity_stat,
                'exposure' => $this->exposure_stat,
            ]
        ];
    }
}
