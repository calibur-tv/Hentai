<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
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
        $masterSlug = $request->get('slug');
        if ($visitor->slug === $masterSlug)
        {
            return $this->resOK([
                'relation' => 'self'
            ]);
        }

        $userActivity = new UserActivity();
        $userExposure = new UserExposure();

        $userActivity->set($visitor->slug);
        $userExposure->set($masterSlug);

        // todo
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
        $result = $userDailySign->sign($user);

        if (false === $result)
        {
            return $this->resErrBad('今天已经签过到了');
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
