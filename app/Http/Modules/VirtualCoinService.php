<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019/2/7
 * Time: 上午11:56
 */

namespace App\Http\Modules;


use App\User;
use App\Models\VirtualCoin;

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
    public function getUserBalance($userId)
    {
        $get = VirtualCoin
            ::where('user_id', $userId)
            ->where('amount', '>', 0)
            ->withTrashed()
            ->sum('amount');

        $set = VirtualCoin
            ::where('user_id', $userId)
            ->where('amount', '<', 0)
            ->withTrashed()
            ->sum('amount');

        return [
            'get' => sprintf("%.2f", $get),
            'set' => -sprintf("%.2f", $set)
        ];
    }

    // 每日签到送团子
    public function daySign($userId, $amount = 1)
    {
        $this->addCoin($userId, $amount, 0, 0, 0);
    }

    // 邀请用户注册赠送团子
    public function inviteUser($oldUserId, $newUserId, $amount = 5)
    {
        $this->addCoin($oldUserId, $amount, 1, 0, $newUserId);
    }

    // 被邀请注册用户送团子
    public function invitedNewbieCoinGift($oldUserId, $newUserId, $amount = 2)
    {
        $this->addCoin($newUserId, $amount, 20, 0, $oldUserId);
    }

    // 用户活跃送团子
    public function userActivityReward($userId)
    {
        $this->addCoin($userId, 1, 2, 0, 0);
    }

    // 管理活跃送光玉
    public function adminActiveReward($userId)
    {
        $this->addMoney($userId, 1, 19, 0, 0);
    }

    // 给用户赠送团子
    public function coinGift($toUserId, $amount)
    {
        $this->addCoin($toUserId, $amount, 16, 0, 0);
    }

    // 给用户赠送光玉
    public function lightGift($toUserId, $amount)
    {
        $this->addMoney($toUserId, $amount, 17, 0, 0);
    }

    private function useCoinFirst($userId, $amount, $channel_type, $product_id, $about_user_id)
    {
        if ($amount > 0)
        {
            $amount = -$amount;
        }

        $balance = User
            ::where('id', $userId)
            ->withTrashed()
            ->select('virtual_coin', 'money_coin')
            ->first()
            ->toArray();

        if ($balance['virtual_coin'] + $balance['money_coin'] + $amount < 0)
        {
            return false;
        }

        VirtualCoin::create([
            'user_id' => $userId,
            'amount' => $amount,
            'channel_type' => $channel_type,
            'product_id' => $product_id,
            'about_user_id' => $about_user_id
        ]);

        if ($balance['virtual_coin'] + $amount < 0)
        {
            User
                ::where('id', $userId)
                ->withTrashed()
                ->increment('virtual_coin', -$balance['virtual_coin']);

            User
                ::where('id', $userId)
                ->withTrashed()
                ->increment('money_coin', $balance['virtual_coin'] + $amount);
        }
        else
        {
            User
                ::where('id', $userId)
                ->withTrashed()
                ->increment('virtual_coin', $amount);
        }

        return true;
    }

    private function useMoneyFirst($userId, $amount, $channel_type, $product_id, $about_user_id)
    {
        if ($amount > 0)
        {
            $amount = -$amount;
        }

        $balance = User
            ::where('id', $userId)
            ->withTrashed()
            ->select('virtual_coin', 'money_coin')
            ->first()
            ->toArray();

        if ($balance['virtual_coin'] + $balance['money_coin'] + $amount < 0)
        {
            return false;
        }

        VirtualCoin::create([
            'user_id' => $userId,
            'amount' => $amount,
            'channel_type' => $channel_type,
            'product_id' => $product_id,
            'about_user_id' => $about_user_id
        ]);

        if ($balance['money_coin'] + $amount < 0)
        {
            User
                ::where('id', $userId)
                ->withTrashed()
                ->increment('money_coin', -$balance['money_coin']);

            User
                ::where('id', $userId)
                ->withTrashed()
                ->increment('virtual_coin', $balance['money_coin'] + $amount);
        }
        else
        {
            User
                ::where('id', $userId)
                ->withTrashed()
                ->increment('money_coin', $amount);
        }

        return true;
    }

    private function addCoin($userId, $amount, $channel_type, $product_id, $about_user_id)
    {
        if ($amount < 0)
        {
            $amount = +$amount;
        }

        VirtualCoin::create([
            'user_id' => $userId,
            'amount' => $amount,
            'channel_type' => $channel_type,
            'product_id' => $product_id,
            'about_user_id' => $about_user_id
        ]);

        User
            ::where('id', $userId)
            ->withTrashed()
            ->increment('virtual_coin', $amount);
    }

    private function addMoney($userId, $amount, $channel_type, $product_id, $about_user_id)
    {
        if ($amount < 0)
        {
            $amount = +$amount;
        }

        VirtualCoin::create([
            'user_id' => $userId,
            'amount' => $amount,
            'channel_type' => $channel_type,
            'product_id' => $product_id,
            'about_user_id' => $about_user_id
        ]);

        User
            ::where('id', $userId)
            ->withTrashed()
            ->increment('money_coin', $amount);
    }
}
