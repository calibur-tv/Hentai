<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Modules\Counter\UserPatchCounter;
use App\Http\Modules\DailyRecord\UserActivity;
use App\Http\Modules\DailyRecord\UserDailySign;
use App\Http\Modules\DailyRecord\UserExposure;
use App\Http\Repositories\PinRepository;
use App\Http\Repositories\TagRepository;
use App\Http\Repositories\UserRepository;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $targetSlug = $request->get('slug');
        if (!$targetSlug)
        {
            return $this->resErrBad();
        }

        $userRepository = new UserRepository();
        $target = $userRepository->item($targetSlug);
        if (is_null($target))
        {
            return $this->resErrNotFound();
        }

        $userPatchCounter = new UserPatchCounter();
        $patch = $userPatchCounter->all($targetSlug);
        $visitor = $request->user();
        $visitorSlug = $visitor->slug;
        if (!$visitor)
        {
            return $this->resOK($patch);
        }

        if ($visitorSlug === $targetSlug)
        {
            $userDailySign = new UserDailySign();
            $patch['daily_signed'] = $userDailySign->check($targetSlug);

            return $this->resOK($patch);
        }

        $target = User
            ::where('slug', $targetSlug)
            ->first();

        $userActivity = new UserActivity();
        $userExposure = new UserExposure();
        $userActivity->set($visitorSlug);
        $userExposure->set($targetSlug);
        $userPatchCounter->add($targetSlug, 'visit_count');

        $patch['is_following'] = $visitor->isFollowing($target);
        $patch['is_followed_by'] = $visitor->isFollowedBy($target);

        return $this->resOK($patch);
    }

    public function timeline(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|string',
            'page' => 'required|integer',
            'count' => 'required|integer'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $slug = $request->get('slug');
        $page = $request->get('page') ?: 1;
        $count = $request->get('count') ?: 10;

        $userRepository = new UserRepository();

        $user = $userRepository->item($slug);
        if (is_null($user))
        {
            return $this->resErrNotFound();
        }

        $idsObj = $userRepository->timeline($slug, false, $page - 1, $count);

        if (!$idsObj['total'])
        {
            return $this->resOK($idsObj);
        }

        $result = [];
        $tagRepository = new TagRepository();
        $pinRepository = new PinRepository();

        foreach ($idsObj['result'] as $row)
        {
            $type = $row['type'];
            $slug = $row['slug'];
            if ($type == 0)
            {
                $result[] = array_merge($row, [
                    'data' => $userRepository->item($slug)
                ]);
            }
            else if ($type == 1)
            {
                $result[] = array_merge($row, [
                    'data' => $tagRepository->item($slug)
                ]);
            }
            else if ($type == 2)
            {
                $result[] = array_merge($row, [
                    'data' => $tagRepository->item($slug)
                ]);
            }
            else if ($type == 3)
            {
                $result[] = array_merge($row, [
                    'data' => $pinRepository->item($slug)
                ]);
            }
        }
        $idsObj['result'] = $result;

        return $this->resOK($idsObj);
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
        $birthday = $request->get('birthday') ? date('Y-m-d H:m:s', (int)$request->get('birthday')) : null;

        $user->updateProfile([
            'nickname' => $request->get('nickname'),
            'signature' => $request->get('signature'),
            'sex' => $request->get('sex'),
            'avatar' => $request->get('avatar'),
            'banner' => $request->get('banner'),
            'sex_secret' => $request->get('sex_secret'),
            'birthday' => $birthday,
            'birth_secret' => $request->get('birth_secret')
        ]);

        return $this->resOK();
    }

    /**
     * 每日签到
     */
    public function dailySign(Request $request)
    {
        $user = $request->user();

        $userDailySign = new UserDailySign();
        $result = $userDailySign->sign($user);

        if (false === $result)
        {
            return $this->resErrBad('今天已经签过到了');
        }

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
                $result[$item] = [
                    'is_following' => true,
                    'is_followed_by' => true
                ];
            }
            else if (in_array($item, $userFollowings))
            {
                $result[$item] = [
                    'is_following' => true,
                    'is_followed_by' => false
                ];
            }
            else if ($item === $userSlug)
            {
                $result[$item] = [
                    'is_following' => false,
                    'is_followed_by' => false
                ];
            }
            else if (in_array($item, $userFollowers))
            {
                $result[$item] = [
                    'is_following' => false,
                    'is_followed_by' => true
                ];
            }
            else
            {
                $result[$item] = [
                    'is_following' => false,
                    'is_followed_by' => false
                ];
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
}
