<?php


namespace App\Http\Modules\DailyRecord;


use App\Http\Repositories\UserRepository;
use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class UserDailySign
{
    private $table = 'daily_records';
    private $record_type = 0;

    public function sign($user)
    {
        $userId = $user->id;
        $userSlug = $user->slug;

        $signed = $this->check($userId);
        if ($signed)
        {
            return false;
        }

        // 设为已签到
        Redis::SET($this->sign_cache_key($userId), 1);
        $now = Carbon::now();
        // 记录签到
        DB
            ::table($this->table)
            ->insert([
                'record_type' => $this->record_type,
                'record_id' => $userId,
                'day' => $now
            ]);

        // 最后签到时间和总签到次数
        User::where('id', $userId)
            ->increment(
                'total_sign_count', 1,
                [
                    'latest_signed_at' => $now
                ]
            );

        // 更新连续签到次数
        $continuous_sign_count = User
            ::where('id', $userId)
            ->pluck('continuous_sign_count')
            ->first();

        if ($continuous_sign_count < 0)
        {
            User::where('id', $userId)
                ->update([
                    'continuous_sign_count' => 0
                ]);
        }
        else
        {
            User::where('id', $userId)
                ->increment('continuous_sign_count');
        }

        // 修改用户的活跃度
        $userActivity = new UserActivity();
        $userActivity->set($userSlug, 3);

        // TODO：给用户发团子

        // 刷新用户缓存
        $userRepository = new UserRepository();
        $userRepository->item($userSlug, true);

        return true;
    }

    public function check($userId)
    {
        $signed = Redis::GET($this->sign_cache_key($userId));
        if (null !== $signed)
        {
            return (boolean)$signed;
        }

        $signCount = DB
            ::table($this->table)
            ->where('record_type', $this->record_type)
            ->where('record_id', $userId)
            ->where('day', '>=', Carbon::now()->today())
            ->count();

        $cacheKey = $this->sign_cache_key($userId);
        Redis::SET($cacheKey, $signCount);
        Redis::EXPIRE($cacheKey, daily_cache_expire());

        return (boolean)$signCount;
    }

    private function sign_cache_key($userId)
    {
        return 'daily_record_0_' . $userId . '_' . date('Y-m-d');
    }
}
