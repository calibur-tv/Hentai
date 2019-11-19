<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019/2/7
 * Time: 上午11:56
 */

namespace App\Http\Modules;


use App\Models\VirtualCoinRecord;
use App\User;

class VirtualCoinService
{
    // 拥有光玉 + 团子个数
    public function hasMoneyCount($currentUser)
    {
        return floatval($currentUser->money_coin) + floatval($currentUser->virtual_coin);
    }

    // 拥有光玉个数
    public function hasLightCount($currentUser)
    {
        return floatval($currentUser->money_coin);
    }

    // 拥有团子个数
    public function hasCoinCount($currentUser)
    {
        return floatval($currentUser->virtual_coin);
    }

    // 用户的收入支出成交额
    public function getUserBalance($userSlug)
    {
        $get = VirtualCoinRecord
            ::where('from_user_slug', $userSlug)
            ->where('order_amount', '>', 0)
            ->withTrashed()
            ->sum('order_amount');

        $set = VirtualCoinRecord
            ::where('from_user_slug', $userSlug)
            ->where('order_amount', '<', 0)
            ->withTrashed()
            ->sum('order_amount');

        return [
            'get' => sprintf("%.2f", $get),
            'set' => -sprintf("%.2f", $set)
        ];
    }

    // 每日签到送团子
    public function daySign($userSlug, $amount = 1)
    {
        $this->addCoin($userSlug, $amount, 0);
    }

    // 给帖子投食
    public function rewardPin($fromUserSlug, $toUserSlug, $productSlug, $amount = 1)
    {
        $channelType = 1;
        $result = $this->useCoinFirst($fromUserSlug, $amount, $channelType, $productSlug, $toUserSlug);
        if (!$result)
        {
            return false;
        }
        $this->addMoney($toUserSlug, $amount, $channelType, $productSlug, $fromUserSlug);

        return true;
    }

    public function buyIdolStock($userSlug, $idolSlug, $amount)
    {
        return $this->useCoinFirst($userSlug, $amount, 2, $idolSlug, '');
    }

    // 用户活跃送团子
    public function userActivityReward($userSlug)
    {
        $this->addCoin($userSlug, 1, 3);
    }

    // 管理活跃送光玉
    public function adminActiveReward($userSlug)
    {
        $this->addMoney($userSlug, 1, 4);
    }

    // 给用户赠送团子
    public function coinGift($toUserSlug, $amount)
    {
        $this->addCoin($toUserSlug, $amount, 5);
    }

    // 给用户赠送光玉
    public function lightGift($toUserSlug, $amount)
    {
        $this->addMoney($toUserSlug, $amount, 6);
    }

    // 邀请用户注册赠送团子
    public function inviteUser($oldUserSlug, $newUserSlug, $amount = 5)
    {
        $this->addCoin($oldUserSlug, $amount, 7, '', $newUserSlug);
    }

    // 被邀请注册用户送团子
    public function invitedNewbieCoinGift($oldUserSlug, $newUserSlug, $amount = 2)
    {
        $this->addCoin($newUserSlug, $amount, 8, '', $oldUserSlug);
    }

    // 四舍六入算法
    public function calculate($num, $precision = 2)
    {
        $pow = pow(10, $precision);
        if (
            (floor($num * $pow * 10) % 5 == 0) &&
            (floor($num * $pow * 10) == $num * $pow * 10) &&
            (floor($num * $pow) % 2 == 0)
        )
        {
            return floor($num * $pow) / $pow;
        } else {
            return round($num, $precision);
        }
    }

    private function useCoinFirst($user_slug, $amount, $channel_type, $product_slug, $about_user_slug)
    {
        if ($amount > 0)
        {
            $amount = -$amount;
        }

        $balance = User
            ::where('slug', $user_slug)
            ->withTrashed()
            ->select('virtual_coin', 'money_coin')
            ->first()
            ->toArray();

        if ($balance['virtual_coin'] + $balance['money_coin'] + $amount < 0)
        {
            return false;
        }

        VirtualCoinRecord::create([
            'from_user_slug' => $user_slug,
            'order_amount' => $amount,
            'target_type' => $channel_type,
            'target_slug' => $product_slug,
            'to_user_slug' => $about_user_slug
        ]);

        if ($balance['virtual_coin'] + $amount < 0)
        {
            User
                ::where('slug', $user_slug)
                ->withTrashed()
                ->increment('virtual_coin', -$balance['virtual_coin']);

            User
                ::where('slug', $user_slug)
                ->withTrashed()
                ->increment('money_coin', $balance['virtual_coin'] + $amount);
        }
        else
        {
            User
                ::where('slug', $user_slug)
                ->withTrashed()
                ->increment('virtual_coin', $amount);
        }

        return true;
    }

    private function useMoneyFirst($user_slug, $amount, $channel_type, $product_slug, $about_user_slug)
    {
        if ($amount > 0)
        {
            $amount = -$amount;
        }

        $balance = User
            ::where('slug', $user_slug)
            ->withTrashed()
            ->select('virtual_coin', 'money_coin')
            ->first()
            ->toArray();

        if ($balance['virtual_coin'] + $balance['money_coin'] + $amount < 0)
        {
            return false;
        }

        VirtualCoinRecord::create([
            'from_user_slug' => $user_slug,
            'to_user_slug' => $about_user_slug,
            'order_amount' => $amount,
            'target_type' => $channel_type,
            'target_slug' => $product_slug,
        ]);

        if ($balance['money_coin'] + $amount < 0)
        {
            User
                ::where('slug', $user_slug)
                ->withTrashed()
                ->increment('money_coin', -$balance['money_coin']);

            User
                ::where('slug', $user_slug)
                ->withTrashed()
                ->increment('virtual_coin', $balance['money_coin'] + $amount);
        }
        else
        {
            User
                ::where('slug', $user_slug)
                ->withTrashed()
                ->increment('money_coin', $amount);
        }

        return true;
    }

    private function addCoin($user_slug, $amount, $channel_type, $product_slug = '', $about_user_slug = '')
    {
        if ($amount < 0)
        {
            $amount = +$amount;
        }

        VirtualCoinRecord::create([
            'from_user_slug' => $user_slug,
            'to_user_slug' => $about_user_slug,
            'order_amount' => $amount,
            'target_type' => $channel_type,
            'target_slug' => $product_slug,
        ]);

        User
            ::where('slug', $user_slug)
            ->withTrashed()
            ->increment('virtual_coin', $amount);
    }

    private function addMoney($user_slug, $amount, $channel_type, $product_slug = '', $about_user_slug = '')
    {
        if ($amount < 0)
        {
            $amount = +$amount;
        }

        VirtualCoinRecord::create([
            'from_user_slug' => $user_slug,
            'to_user_slug' => $about_user_slug,
            'order_amount' => $amount,
            'target_type' => $channel_type,
            'target_slug' => $product_slug,
        ]);

        User
            ::where('slug', $user_slug)
            ->withTrashed()
            ->increment('money_coin', $amount);
    }
}
