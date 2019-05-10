<?php


namespace App\Http\Modules\Cooling;

use App\Http\Repositories\Repository;
use App\Api\V1\Repositories\UserRepository;
use App\Api\V1\Services\Owner\BangumiManager;
use App\Http\Modules\VirtualCoinService;
use App\Models\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;

class UserActivity extends Cooling
{
    public function __construct()
    {
        parent::__construct('user_day_activity');
    }

    protected function hook($userId, $score)
    {
        if ($score < 100)
        {
            return;
        }
        $userRepository = new UserRepository();
        $user = $userRepository->item($userId);
        if ($user['banned_to'])
        {
            return;
        }
        $virtualCoinService = new VirtualCoinService();
        // 送团子
        $virtualCoinService->userActivityReward($userId);
        $this->createNotification(42, $userId);
        $bangumiManager = new BangumiManager();
        if (!$bangumiManager->isAManager($userId))
        {
            return;
        }
        // 送团子
        $virtualCoinService->masterActiveReward($userId);
        $this->createNotification(43, $userId);
    }

    protected function createNotification($type, $userId)
    {
        $now = Carbon::now();
        $id = Notification::insertGetId([
            'type' => $type,
            'model_id' => 0,
            'comment_id' => 0,
            'reply_id' => 0,
            'to_user_id' => $userId,
            'from_user_id' => 0,
            'created_at' => $now,
            'updated_at' => $now
        ]);
        $repository = new Repository();
        $repository->ListInsertBefore('user-' . $userId . '-notification-ids', $id);
        if (Redis::EXISTS('user_' . $userId . '_notification_count'))
        {
            Redis::INCRBY('user_' . $userId . '_notification_count', 1);
        }
    }
}
