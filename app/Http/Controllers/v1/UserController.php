<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Modules\Counter\UserBeFollowedCounter;
use App\Http\Modules\Counter\UserFollowingCounter;
use App\Http\Modules\Counter\UserFriendCounter;
use App\Http\Modules\DailyRecord\UserActivity;
use App\Http\Modules\DailyRecord\UserDailySign;
use App\Http\Modules\DailyRecord\UserExposure;
use App\Http\Repositories\UserRepository;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Mews\Purifier\Facades\Purifier;

class UserController extends Controller
{
    public function show(Request $request)
    {
        $slug = $request->get('slug');
        $userRepository = new UserRepository();
        $user = $userRepository->item($slug);

        if (is_null($user))
        {
            return $this->resErrNotFound();
        }

        return $this->resOK($user);
    }

    /**
     * 用户访问的数据补丁
     * 关联关系和用户的动态数据，关注数，粉丝数
     */
    public function patch(Request $request)
    {
        $visitor = $request->user();
        $targetSlug = $request->get('slug');
        if (!$targetSlug)
        {
            return $this->resErrBad();
        }

        $visitorSlug = $visitor->slug;

        $userBeFollowedCounter = new UserBeFollowedCounter();
        $userFollowingCounter = new UserFollowingCounter();

        $followers_count = $userBeFollowedCounter->get($targetSlug);
        $following_count = $userFollowingCounter->get($targetSlug);
        if ($visitorSlug === $targetSlug)
        {
            return $this->resOK([
                'followers_count' => $followers_count,
                'following_count' => $following_count,
                'relation' => 'self'
            ]);
        }

        $target = User
            ::where('slug', $targetSlug)
            ->first();

        if (is_null($target))
        {
            return $this->resErrNotFound();
        }

        $relation = $this->convertUserRelation($visitor->isFollowing($target), $target->isFollowing($visitor));

        $userActivity = new UserActivity();
        $userExposure = new UserExposure();
        $userActivity->set($visitorSlug);
        $userExposure->set($targetSlug);

        return $this->resOK([
            'followers_count' => $followers_count,
            'following_count' => $following_count,
            'relation' => $relation
        ]);
    }

    /**
     * 更新用户信息
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sex' => 'required',
            'signature' => 'string|min:0|max:150',
            'nickname' => 'required|min:1|max:14',
            'birth_secret' => 'required|boolean',
            'birthday' => 'required',
            'avatar' => 'required|string',
            'banner' => 'required|string',
            'sex_secret' => 'required|boolean'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $user = $request->user();
        $slug = $user->slug;
        $birthday = $request->get('birthday') ? date('Y-m-d H:m:s', (int)$request->get('birthday')) : null;
        $avatar = $request->get('avatar');
        $banner = $request->get('banner');

        User
            ::where('slug', $slug)
            ->update([
                'nickname' => $request->get('nickname'),
                'signature' => Purifier::clean($request->get('signature')),
                'sex' => $request->get('sex'),
                'avatar' => $avatar,
                'banner' => $banner,
                'sex_secret' => $request->get('sex_secret'),
                'birthday' => $birthday,
                'birth_secret' => $request->get('birth_secret')
            ]);

        $userRepository = new UserRepository();
        $userRepository->item($slug, true);

        return $this->resOK();
    }

    /**
     * 每日签到
     */
    public function dailySign(Request $request)
    {
        $user = $request->user();

        $userDailySign = new UserDailySign();
        $result = $userDailySign->sign($user->slug);

        if (false === $result)
        {
            return $this->resErrBad('今天已经签过到了');
        }

        return $this->resOK($result);
    }

    /**
     * 用户关注
     */
    public function toggleFollow(Request $request)
    {
        $user = $request->user();
        $targetSlug = $request->get('slug');
        $mineSlug = $user->slug;

        $target = User
            ::where('slug', $targetSlug)
            ->first();

        if (is_null($target))
        {
            return $this->resErrNotFound();
        }

        $userFollowingCounter = new UserFollowingCounter();
        $userBeFollowedCounter = new UserBeFollowedCounter();

        $isFollowing = $user->isFollowing($target); // 我是否关注了 TA
        $isFollowMe = $target->isFollowing($user);  // TA 是否关注了我

        if (!$isFollowing) // 如果未关注
        {
            $hasFollowingCount = $userFollowingCounter->get($mineSlug);
            // 100人是关注的上限
            if ($hasFollowingCount >= 100)
            {
                return $this->resErrRole('最多关注100个人');
            }

            $user->follow($target);
            $userFollowingCounter->add($mineSlug);
            $userBeFollowedCounter->add($targetSlug);
        }
        else // 如果已关注
        {
            $user->unfollow($target);
            $userFollowingCounter->add($mineSlug, -1);
            $userBeFollowedCounter->add($targetSlug, -1);
        }

        $isFollowing = !$isFollowing; // 我关注的结果

        $userRepository = new UserRepository();
        if ($isFollowMe)
        {
            // 无论我是否取消关注，都刷新彼此朋友列表的缓存
            $userRepository->friends($targetSlug, true);
            $userRepository->friends($mineSlug, true);

            $userFriendCounter = new UserFriendCounter();
            $num = $isFollowing ? 1 : -1;
            // 改变彼此朋友的个数
            $userFriendCounter->add($targetSlug, $num);
            $userFriendCounter->add($mineSlug, $num);
        }
        else
        {
            // 刷新TA的粉丝列表
            $userRepository->followers($targetSlug, true);
            // 刷新我的关注列表
            $userRepository->followings($mineSlug, true);
        }
        // 返回彼此的关系
        $result = $this->convertUserRelation($isFollowing, $isFollowMe);

        // TODO 消息通知

        return $this->resOK($result);
    }


    /**
     * 用户关系
     */
    public function getUserRelation(Request $request)
    {
        $slug = $request->get('slug');
        $take = $request->get('count') ?: 15;
        $seenIds = $request->get('seen_ids') ? explode(',', $request->get('seen_ids')) : [];
        $relation = $request->get('relation');

        $userRepository = new UserRepository();
        $user = $userRepository->item($slug);
        if (is_null($user))
        {
            return $this->resErrNotFound();
        }

        if ($relation === 'follower')
        {
            $idsObj = $userRepository->followers($slug, false, $seenIds, $take);
        }
        else if ($relation === 'following')
        {
            $idsObj = $userRepository->followings($slug);
        }
        else if ($relation === 'friend')
        {
            $idsObj = $userRepository->friends($slug);
        }
        else
        {
            return $this->resErrBad();
        }

        $idsObj['result'] = $userRepository->list($idsObj['result']);

        return $this->resOK($idsObj);
    }


    public function detectUserRelation(Request $request)
    {
        $user = $request->user();
        if (!$user)
        {
            return $this->resOK([]);
        }

        $targets = $request->get('targets') ? explode(',', $request->get('targets')) : [];
        $type = 'user';
        $userSlug = $user->slug;

        if (empty($targets))
        {
            return $this->resErrBad();
        }

        $userRepository = new UserRepository();
        $userFollowers = $userRepository->followers($userSlug, false, [], 999999999)['result'];
        $userFollowings = $userRepository->followings($userSlug)['result'];
        $userFriends = $userRepository->friends($userSlug)['result'];

        $result = [];
        foreach ($targets as $item)
        {
            if (in_array($item, $userFriends))
            {
                $result[$item] = 'friend';
            }
            else if (in_array($item, $userFollowings))
            {
                $result[$item] = 'following';
            }
            else if (in_array($item, $userFollowers))
            {
                $result[$item] = 'follower';
            }
            else if ($item === $userSlug)
            {
                $result[$item] = 'self';
            }
            else
            {
                $result[$item] = 'stranger';
            }
        }

        return $this->resOK($result);
    }

    /**
     * 审核中的用户（修改用户数据的时候有可能进审核）
     */
    public function trials(Request $request)
    {

    }

    /**
     * 审核通过
     */
    public function resolve(Request $request)
    {

    }

    /**
     * 审核不通过
     */
    public function reject(Request $request)
    {

    }

    protected function convertUserRelation($currentFollowTarget, $targetFollowCurrent)
    {
        // 'friend', 'follower', 'following', 'stranger'
        if ($currentFollowTarget && $targetFollowCurrent)
        {
            $result = 'friend';
        }
        else if ($currentFollowTarget && !$targetFollowCurrent)
        {
            $result = 'following';
        }
        else if (!$currentFollowTarget && $targetFollowCurrent)
        {
            $result = 'follower';
        }
        else
        {
            $result = 'stranger';
        }

        return $result;
    }
}
