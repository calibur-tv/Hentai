<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers\User;

use App\Http\Modules\DailyRecord\UserDailySign;
use Illuminate\Http\Resources\Json\JsonResource;

class UserHomeResource extends JsonResource
{
    public function toArray($request)
    {
        $userDailySign = new UserDailySign();

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
                'followers_count' => $this->followers_count,
                'following_count' => $this->following_count,
                'relation' => 'unknown'
            ],
            'balance' => [
                'coin' => (float)$this->virtual_coin,
                'money' => (float)$this->money_coin,
            ],
            'sign' => [
                'daily_signed' => $userDailySign->check($this->slug),
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
