<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Repositorys\v1\UserRepository;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
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
     * 更新用户信息
     */
    public function update_info(Request $request)
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
        $userId = $user->id;
        $birthday = $request->get('birthday') ? date('Y-m-d H:m:s', (int)$request->get('birthday')) : null;
        $avatar = $request->get('avatar');
        $banner = $request->get('banner');

        User
            ::where('id', $userId)
            ->update([
                'nickname' => $request->get('nickname'),
                'signature' => Purifier::clean($request->get('signature')),
                'sex' => $request->get('sex'),
                'avatar' => $this->convertImagePath($avatar),
                'banner' => $this->convertImagePath($banner),
                'sex_secret' => $request->get('sex_secret'),
                'birthday' => $birthday,
                'birth_secret' => $request->get('birth_secret')
            ]);

        $userRepository = new UserRepository();
        Redis::DEL($userRepository->item_cache_key($userId));

        return $this->resOK();
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
