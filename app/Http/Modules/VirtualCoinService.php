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
    public function getUserBalance($userSlug)
    {
        $get = VirtualCoin
            ::where('user_slug', $userSlug)
            ->where('amount', '>', 0)
            ->withTrashed()
            ->sum('amount');

        $set = VirtualCoin
            ::where('user_slug', $userSlug)
            ->where('amount', '<', 0)
            ->withTrashed()
            ->sum('amount');

        return [
            'get' => sprintf("%.2f", $get),
            'set' => -sprintf("%.2f", $set)
        ];
    }

    // 每日签到送团子
    public function daySign($userSlug, $amount = 1)
    {
        $this->addCoin($userSlug, $amount, 0, 0, 0);
    }

    // 邀请用户注册赠送团子
    public function inviteUser($oldUserSlug, $newUserSlug, $amount = 5)
    {
        $this->addCoin($oldUserSlug, $amount, 1, 0, $newUserSlug);
    }

    // 被邀请注册用户送团子
    public function invitedNewbieCoinGift($oldUserSlug, $newUserSlug, $amount = 2)
    {
        $this->addCoin($newUserSlug, $amount, 20, 0, $oldUserSlug);
    }

    // 用户活跃送团子
    public function userActivityReward($userSlug)
    {
        $this->addCoin($userSlug, 1, 2, 0, 0);
    }

    // 管理活跃送光玉
    public function adminActiveReward($userSlug)
    {
        $this->addMoney($userSlug, 1, 19, 0, 0);
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

    private function useCoinFirst($user_slug, $amount, $channel_type, $product_id, $about_user_slug)
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

        VirtualCoin::create([
            'user_slug' => $user_slug,
            'amount' => $amount,
            'channel_type' => $channel_type,
            'product_id' => $product_id,
            'about_user_slug' => $about_user_slug
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

    private function useMoneyFirst($user_slug, $amount, $channel_type, $product_id, $about_user_slug)
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

        VirtualCoin::create([
            'user_slug' => $user_slug,
            'amount' => $amount,
            'channel_type' => $channel_type,
            'product_id' => $product_id,
            'about_user_slug' => $about_user_slug
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

    private function addCoin($user_slug, $amount, $channel_type, $product_id, $about_user_slug)
    {
        if ($amount < 0)
        {
            $amount = +$amount;
        }

        VirtualCoin::create([
            'user_slug' => $user_slug,
            'amount' => $amount,
            'channel_type' => $channel_type,
            'product_id' => $product_id,
            'about_user_slug' => $about_user_slug
        ]);

        User
            ::where('slug', $user_slug)
            ->withTrashed()
            ->increment('virtual_coin', $amount);
    }

    private function addMoney($user_slug, $amount, $channel_type, $product_id, $about_user_slug)
    {
        if ($amount < 0)
        {
            $amount = +$amount;
        }

        VirtualCoin::create([
            'user_slug' => $user_slug,
            'amount' => $amount,
            'channel_type' => $channel_type,
            'product_id' => $product_id,
            'about_user_slug' => $about_user_slug
        ]);

        User
            ::where('slug', $user_slug)
            ->withTrashed()
            ->increment('money_coin', $amount);
    }
}
