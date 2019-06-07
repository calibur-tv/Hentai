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
            'level' => $this->level,
            'sex' => $this->sex_secret ? -1 : $this->sex,
            'birthday' => $this->birth_secret ? -1 : $this->birthday,
            'daily_signed' => $userDailySign->check($this->id),
            'continuous_sign_count' => $this->continuous_sign_count,
            'total_sign_count' => $this->total_sign_count,
            'latest_signed_at' => $this->latest_signed_at,
            'activity_stat' => $this->activity_stat,
            'exposure_stat' => $this->exposure_stat,
            'signature' => $this->signature,
            'title' => $this->title
        ];
    }
}
