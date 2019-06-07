<?php


namespace App\Http\Modules\DailyRecord;


use App\Http\Modules\VirtualCoinService;
use App\Http\Repositories\UserRepository;
use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class UserDailySign
{
    private $table = 'daily_records';
    private $record_type = 0;

    public function sign($userSlug)
    {
        $signed = $this->check($userSlug);
        if ($signed)
        {
            return false;
        }

        // 设为已签到
        Redis::SET($this->sign_cache_key($userSlug), 1);
        $now = Carbon::now();
        $addCoinCount = 1;
        // 记录签到
        DB
            ::table($this->table)
            ->insert([
                'record_type' => $this->record_type,
                'record_slug' => $userSlug,
                'value' => $addCoinCount,
                'day' => $now
            ]);

        // 最后签到时间和总签到次数
        User::where('slug', $userSlug)
            ->increment(
                'total_sign_count', 1,
                [
                    'latest_signed_at' => $now
                ]
            );

        // 更新连续签到次数
        $continuous_sign_count = User
            ::where('slug', $userSlug)
            ->pluck('continuous_sign_count')
            ->first();

        if ($continuous_sign_count < 0)
        {
            User::where('slug', $userSlug)
                ->update([
                    'continuous_sign_count' => 0
                ]);
        }
        else
        {
            User::where('slug', $userSlug)
                ->increment('continuous_sign_count');
        }

        // 修改用户的活跃度，默认 + 3
        $userActivity = new UserActivity();
        $userActivity->set($userSlug, 3);

        // 给用户发团子
        $virtualCoinService = new VirtualCoinService();
        $virtualCoinService->daySign($userSlug, $addCoinCount);

        // 刷新用户缓存
        $userRepository = new UserRepository();
        $userRepository->item($userSlug, true);

        return [
            'message' => "签到成功，团子+{$addCoinCount}",
            'add_coin_count' => $addCoinCount,
            'sign_at' => $now,
            'continuous_sign_count' => $continuous_sign_count < 0 ? 0 : $continuous_sign_count + 1
        ];
    }

    public function check($userSlug)
    {
        $signed = Redis::GET($this->sign_cache_key($userSlug));
        if (null !== $signed)
        {
            return (boolean)$signed;
        }

        $signCount = DB
            ::table($this->table)
            ->where('record_type', $this->record_type)
            ->where('record_slug', $userSlug)
            ->where('day', '>=', Carbon::now()->today())
            ->count();

        $cacheKey = $this->sign_cache_key($userSlug);
        Redis::SET($cacheKey, $signCount);
        Redis::EXPIRE($cacheKey, daily_cache_expire());

        return (boolean)$signCount;
    }

    private function sign_cache_key($userSlug)
    {
        return 'daily_record_' . $this->record_type . '_' . $userSlug . '_' . date('Y-m-d');
    }
}
