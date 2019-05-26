<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;

class DoorController extends Controller
{

    /**
     * 发送手机验证码
     *
     * > 一个通用的接口，通过 `type` 和 `phone_number` 发送手机验证码.
     * 目前支持 `type` 为：
     * 1. `sign_up`，注册时调用
     * 2. `forgot_password`，找回密码时使用
     * 3. `bind_phone`，绑定手机号时使用
     *
     * > 目前返回的数字验证码是`6位`
     *
     * @Post("/door/message")
     *
     * @Parameters({
     *      @Parameter("type", description="上面的某种type", type="string", required=true),
     *      @Parameter("phone_number", description="只支持`11位`的手机号", type="number", required=true),
     *      @Parameter("geetest", description="Geetest认证对象", type="object", required=true)
     * })
     *
     * @Transaction({
     *      @Response(201, body={"code": 0, "data": "短信已发送"}),
     *      @Response(400, body={"code": 40001, "message": "未经过图形验证码认证"}),
     *      @Response(401, body={"code": 40100, "message": "图形验证码认证失败"}),
     *      @Response(400, body={"code": 40003, "message": "各种错误"}),
     *      @Response(503, body={"code": 50310, "message": "短信服务暂不可用或请求过于频繁"})
     * })
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => [
                'required',
                Rule::in(['sign_up', 'forgot_password', 'bind_phone']),
            ],
            'phone_number' => 'required|digits:11'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $phone = $request->get('phone_number');
        $type = $request->get('type');

        if ($type === 'sign_up')
        {
            $museNew = true;
            $mustOld = false;
        }
        else if ($type === 'forgot_password')
        {
            $museNew = false;
            $mustOld = true;
        }
        else if ($type === 'bind_phone')
        {
            $museNew = true;
            $mustOld = false;
        }
        else
        {
            $museNew = false;
            $mustOld = false;
        }

        if ($museNew && !$this->accessIsNew('phone', $phone))
        {
            return $this->resErrBad('手机号已注册');
        }

        if ($mustOld && $this->accessIsNew('phone', $phone))
        {
            return $this->resErrBad('未注册的手机号');
        }

        $authCode = $this->createMessageAuthCode($phone, $type);
        $sms = new Message();

        if ($type === 'sign_up')
        {
            $result = $sms->register($phone, $authCode);
        }
        else if ($type === 'forgot_password')
        {
            $result = $sms->forgotPassword($phone, $authCode);
        }
        else if ($type === 'bind_phone')
        {
            $result = $sms->bindPhone($phone, $authCode);
        }
        else
        {
            return $this->resErrBad();
        }

        if (!$result)
        {
            return $this->resErrServiceUnavailable();
        }

        return $this->resCreated('短信已发送');
    }

    /**
     * 用户注册
     *
     * 目前仅支持使用手机号注册
     *
     * @Post("/door/register")
     *
     * @Parameters({
     *      @Parameter("access", description="手机号", type="number", required=true),
     *      @Parameter("secret", description="`6至16位`的密码", type="string", required=true),
     *      @Parameter("nickname", description="昵称，只能包含`汉字、数字和字母，2~14个字符组成，1个汉字占2个字符`", type="string", required=true),
     *      @Parameter("authCode", description="6位数字的短信验证码", type="number", required=true),
     *      @Parameter("inviteCode", description="邀请码", type="number", required=false),
     * })
     *
     * @Transaction({
     *      @Response(201, body={"code": 0, "data": "JWT-Token"}),
     *      @Response(400, body={"code": 40003, "message": "各种错误"})
     * })
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'access' => 'required|digits:11',
            'secret' => 'required|min:6|max:16',
            'nickname' => 'required|min:1|max:14',
            'authCode' => 'required|digits:6'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        if (!preg_match('/^([a-zA-Z]+|[0-9]+|[\x{4e00}-\x{9fa5}]+)*$/u', $request->get('nickname')))
        {
            return $this->resErrBad('昵称只能包含汉字、数字和字母');
        }

        $access = $request->get('access');

        if (!$this->checkMessageAuthCode($access, 'sign_up', $request->get('authCode')))
        {
            return $this->resErrBad('短信验证码已过期，请重新获取');
        }

        if (!$this->accessIsNew('phone', $access))
        {
            return $this->resErrBad('该手机号已绑定另外一个账号');
        }

        $nickname = $request->get('nickname');
        $zone = $this->createUserZone($nickname);
        $data = [
            'nickname' => $nickname,
            'password' => Crypt::encrypt($request->get('secret')),
            'zone' => $zone,
            'phone' => $access
        ];

        try
        {
            $user = User::create($data);
        }
        catch (\Exception $e)
        {
            app('sentry')->captureException($e);

            return $this->resErrBad('昵称暂不可用，请尝试其它昵称');
        }

        $userId = $user->id;
        $UserIpAddress = new UserIpAddress();
        $UserIpAddress->add(
            explode(', ', $request->headers->get('X-Forwarded-For'))[0],
            $userId
        );

        $inviteCode = $request->get('inviteCode');
        if ($inviteCode)
        {
            $job = (new \App\Jobs\User\InviteUser($userId, $inviteCode));
            dispatch($job);
        }
        else
        {
            $virtualCoinService = new VirtualCoinService();
            $virtualCoinService->coinGift($userId, 1);
        }

        $userRepository = new UserRepository();
        $userRepository->migrateSearchIndex('C', $userId);

        return $this->resCreated($this->responseUser($user));
    }

    /**
     * 用户登录
     *
     * 目前仅支持手机号和密码登录
     *
     * @Post("/door/login")
     *
     * @Parameters({
     *      @Parameter("access", description="手机号", type="number", required=true),
     *      @Parameter("secret", description="6至16位的密码", type="string", required=true),
     *      @Parameter("geetest", description="Geetest认证对象", type="object", required=true)
     * })
     *
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": "JWT-Token"}),
     *      @Response(400, body={"code": 40001, "message": "未经过图形验证码认证"}),
     *      @Response(401, body={"code": 40100, "message": "图形验证码认证失败"}),
     *      @Response(400, body={"code": 40003, "message": "各种错误"})
     * })
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'access' => 'required|digits:11',
            'secret' => 'required|min:6|max:16'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $user = User
            ::where('phone', $request->get('access'))
            ->first();

        if (is_null($user))
        {
            return $this->resErrBad('未注册的账号');
        }

        $matched = $user->verifyPassword($request->get('secret'));

        if (!$matched)
        {
            return $this->resErrBad('密码错误');
        }

        $role = $request->get('role');
        if ($role && $user->cant($role))
        {
            return $this->resErrRole();
        }

        return $this->resOK($user->api_token);
    }

    public function getUserInfo(Request $request)
    {
        $user = $request->user();

        $role = $request->get('role');
        if ($role && $user->cant($role))
        {
            return $this->resErrRole();
        }

        return $this->resOK($user);
    }

    /**
     * 用户登出
     *
     * @Post("/door/logout")
     *
     * @Request(headers={"Authorization": "Bearer JWT-Token"}),
     * @Response(204)
     */
    public function logout()
    {
        return $this->resOK();
    }

    /**
     * APP授权QQ登录、注册
     *
     * @Post("/door/oauth2/qq")
     *
     * @Parameters({
     *      @Parameter("from", description="如果是登录，就是 sign，如果是绑定，就是 bind", type="string", required=true),
     *      @Parameter("access_token", description="登录授权的 access_code", type="string", required=true)
     * })
     *
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": "账号绑定成功"}),
     *      @Response(201, body={"code": 0, "data": "JWT-TOKEN"}),
     *      @Response(400, body={"code": 40003, "message": "请求参数错误"}),
     *      @Response(403, body={"code": 40301, "message": "未登录或已绑定"}),
     *      @Response(503, body={"code": 50301, "message": "服务暂时不可用"})
     * })
     */
    public function qqAuthRedirect(Request $request)
    {
        $from = $request->get('from') === 'bind' ? 'bind' : 'sign';
        $code = $request->get('access_token');
        if (!$code)
        {
            return $this->resErrBad('请求参数错误');
        }

        try
        {
            $socialite = new SocialiteManager(config('services', []));
            $accessToken = new AccessToken([
                'access_token' => $code
            ]);

            $user = $socialite
                ->driver('qq')
                ->user($accessToken);
        }
        catch (\Exception $e)
        {
            app('sentry')->captureException($e);

            return $this->resErrServiceUnavailable('登录失败了~');
        }

        $openId = $user['id'];
        $uniqueId = $user['unionid'];
        $isNewUser = $this->accessIsNew('qq_unique_id', $uniqueId);

        if ($from === 'bind')
        {
            if (!$isNewUser)
            {
                return $this->resErrRole('该QQ号已绑定其它账号');
            }

            $userId = $request->get('id');
            $userZone = $request->get('zone');
            $hasUser = User
                ::where('id', $userId)
                ->where('zone', $userZone)
                ->count();

            if (!$hasUser)
            {
                return $this->resErrRole('继续操作前请先登录');
            }

            User
                ::where('id', $userId)
                ->update([
                    'qq_open_id' => $openId,
                    'qq_unique_id' => $uniqueId
                ]);

            Redis::DEL('user_' . $userId);

            return $this->resOK();
        }

        if ($isNewUser)
        {
            // signUp
            $nickname = $this->getNickname($user['nickname']);
            $zone = $this->createUserZone($nickname);
            $data = [
                'nickname' => $nickname,
                'zone' => $zone,
                'qq_open_id' => $openId,
                'qq_unique_id' => $uniqueId,
                'password' => bcrypt('calibur')
            ];

            try
            {
                $user = User::create($data);
                $userRepository = new UserRepository();
                $userRepository->migrateSearchIndex('C', $user->id);
            }
            catch (\Exception $e)
            {
                app('sentry')->captureException($e);

                return $this->resErrServiceUnavailable('请修改QQ昵称后重试');
            }
        }
        else
        {
            // signIn
            $user = User
                ::where('qq_unique_id', $uniqueId)
                ->first();

            if (is_null($user))
            {
                return $this->resErrRole('该账号不存在了');
            }
        }

        $userId = $user->id;
        $UserIpAddress = new UserIpAddress();
        $UserIpAddress->add(
            explode(', ', $request->headers->get('X-Forwarded-For'))[0],
            $userId
        );

        return $this->resCreated($this->responseUser($user));
    }

    /**
     * APP授权微信登录、注册
     *
     * @Post("/door/oauth2/wechat")
     *
     * @Parameters({
     *      @Parameter("from", description="如果是登录，就是 sign，如果是绑定，就是 bind", type="string", required=true),
     *      @Parameter("access_token", description="登录授权的 access_code", type="string", required=true)
     * })
     *
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": "账号绑定成功"}),
     *      @Response(201, body={"code": 0, "data": "JWT-TOKEN"}),
     *      @Response(400, body={"code": 40003, "message": "请求参数错误"}),
     *      @Response(403, body={"code": 40301, "message": "未登录或已绑定"}),
     *      @Response(503, body={"code": 50301, "message": "服务暂时不可用"})
     * })
     */
    public function wechatAuthRedirect(Request $request)
    {
        $from = $request->get('from') === 'bind' ? 'bind' : 'sign';
        $code = $request->get('access_token');
        $open_id = $request->get('openid');
        if (!$code || !$open_id)
        {
            return $this->resErrBad();
        }

        try
        {
            $socialite = new SocialiteManager(config('services', []));
            $accessToken = new AccessToken([
                'access_token' => $code,
                'openid' => $open_id
            ]);

            $user = $socialite
                ->driver('weixin')
                ->user($accessToken);
        }
        catch (\Exception $e)
        {
            app('sentry')->captureException($e);

            return $this->resErrServiceUnavailable('登录失败了~');
        }

        $openId = $user['original']['openid'];
        $uniqueId = $user['original']['unionid'];
        $isNewUser = $this->accessIsNew('wechat_unique_id', $uniqueId);

        if ($from === 'bind')
        {
            if (!$isNewUser)
            {
                return $this->resErrRole('该微信号已绑定其它账号');
            }

            $userId = $request->get('id');
            $userZone = $request->get('zone');
            $hasUser = User
                ::where('id', $userId)
                ->where('zone', $userZone)
                ->count();

            if (!$hasUser)
            {
                return $this->resErrRole('继续操作前请先登录');
            }

            User
                ::where('id', $userId)
                ->update([
                    'wechat_open_id' => $openId,
                    'wechat_unique_id' => $uniqueId
                ]);

            Redis::DEL('user_' . $userId);

            return $this->resOK();
        }

        if ($isNewUser)
        {
            // signUp
            $nickname = $this->getNickname($user['nickname']);
            $zone = $this->createUserZone($nickname);
            $data = [
                'nickname' => $nickname,
                'zone' => $zone,
                'wechat_open_id' => $openId,
                'wechat_unique_id' => $uniqueId,
                'password' => bcrypt('calibur')
            ];

            try
            {
                $user = User::create($data);
                $userRepository = new UserRepository();
                $userRepository->migrateSearchIndex('C', $user->id);
            }
            catch (\Exception $e)
            {
                app('sentry')->captureException($e);

                return $this->resErrServiceUnavailable('请修改微信昵称后重试');
            }
        }
        else
        {
            // signIn
            $user = User
                ::where('wechat_unique_id', $uniqueId)
                ->first();

            if (is_null($user))
            {
                return $this->resErrRole('该账号不存在了');
            }
        }

        $userId = $user->id;
        $UserIpAddress = new UserIpAddress();
        $UserIpAddress->add(
            explode(', ', $request->headers->get('X-Forwarded-For'))[0],
            $userId
        );

        return $this->resCreated($this->responseUser($user));
    }

    // Todo：绑定第三方账号
    public function bindProvider()
    {

    }

    /**
     * 绑定用户手机号
     *
     * @Post("/door/bind_phone")
     *
     * @Parameters({
     *      @Parameter("id", description="用户id", type="number", required=true),
     *      @Parameter("phone", description="手机号", type="number", required=true),
     *      @Parameter("password", description="6至16位的密码", type="string", required=true),
     *      @Parameter("authCode", description="6位数字的短信验证码", type="number", required=true)
     * })
     *
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": "绑定成功"}),
     *      @Response(400, body={"code": 40003, "message": "参数错误或验证码过期或手机号已占用"})
     * })
     */
    public function bindPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'phone' => 'required|digits:11',
            'password' => 'required|min:6|max:16',
            'authCode' => 'required|digits:6'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $phone = $request->get('phone');

        if (!$this->checkMessageAuthCode($phone, 'bind_phone', $request->get('authCode')))
        {
            return $this->resErrBad('短信验证码已过期，请重新获取');
        }

        if (!$this->accessIsNew('phone', $phone))
        {
            return $this->resErrBad('该手机号已绑定另外一个账号');
        }

        $userId = $request->get('id');
        $hasPhone = User
            ::where('id', $userId)
            ->pluck('phone')
            ->first();

        if ($hasPhone)
        {
            $pattern = '/(\d{3})(\d{4})(\d{4})/i';
            $replacement = '$1****$3';
            $maskPhone = preg_replace($pattern, $replacement, $phone);

            return $this->resErrBad('您的账号已绑定了手机号：' . $maskPhone);
        }

        User::where('id', $userId)
            ->update([
                'phone' => $phone,
                'password' => bcrypt($request->get('password'))
            ]);

        return $this->resOK('手机号绑定成功');
    }

    // Todo：解绑第三方账号，但是账号会被删除
    public function unbindProvider()
    {

    }

    // Todo：更换手机号，需要发短信验证
    public function changePhone()
    {

    }

    // 微信小程序注册用户或获取当前用户的 token
    public function wechatMiniAppLogin(Request $request)
    {
        $user = $request->get('user');
        $encryptedData = $request->get('encrypted_data');
        $iv = $request->get('iv');
        $sessionKey = $request->get('session_key');
        $appId = config('services.wechat_mini_app.app_id');

        $tool = new WXBizDataCrypt($appId, $sessionKey);
        $code = $tool->decryptData($encryptedData, $iv, $data);

        if ($code)
        {
            return $this->resErrServiceUnavailable();
        }

        $data = json_decode($data, true);
        $uniqueId = $data['unionId'];
        $isNewUser = $this->accessIsNew('wechat_unique_id', $uniqueId);
        if ($isNewUser)
        {
            $nickname = $this->getNickname($user['nickName']);
            $zone = $this->createUserZone($nickname);
            $data = [
                'nickname' => $nickname,
                'zone' => $zone,
                'wechat_open_id' => $data['openId'],
                'wechat_unique_id' => $uniqueId,
                'password' => bcrypt('calibur')
            ];

            try
            {
                $user = User::create($data);
                $userRepository = new UserRepository();
                $userRepository->migrateSearchIndex('C', $user->id);
            }
            catch (\Exception $e)
            {
                app('sentry')->captureException($e);

                return $this->resErrServiceUnavailable('请修改微信昵称后重试');
            }
        }
        else
        {
            $user = User
                ::where('wechat_unique_id', $uniqueId)
                ->first();

            if (is_null($user))
            {
                return $this->resErrNotFound('这个用户已经消失了');
            }
        }

        return $this->resOK($this->responseUser($user));
    }

    // 微信小程序获取用户的 session_key 或获取当前用户的 token
    public function wechatMiniAppToken(Request $request)
    {
        $code = $request->get('code');
        if (!$code)
        {
            return $this->resErrBad();
        }

        $client = new Client();
        $appId = config('services.wechat_mini_app.app_id');
        $appSecret = config('services.wechat_mini_app.app_secret');
        $resp = $client->get(
            "https://api.weixin.qq.com/sns/jscode2session?appid={$appId}&secret={$appSecret}&js_code={$code}&grant_type=authorization_code",
            [
                'Accept' => 'application/json'
            ]
        );
        $body = json_decode($resp->body, true);
        $uniqueId = isset($body['unionid']) ? $body['unionid'] : '';
        if (!$uniqueId)
        {
            return $this->resOK([
                'type' => 'key',
                'data' => $body['session_key']
            ]);
        }

        $user = User
            ::where('wechat_unique_id', $uniqueId)
            ->first();

        if (is_null($user))
        {
            return $this->resOK([
                'type' => 'key',
                'data' => $body['session_key']
            ]);
        }

        return $this->resOK([
            'type' => 'token',
            'data' => $this->responseUser($user)
        ]);
    }

    /**
     * 网站获取用户信息
     *
     * 每次页面刷新时调用
     *
     * @Post("/door/refresh")
     *
     * @Request(headers={"Authorization": "Bearer JWT-Token"})
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": "用户对象"}),
     *      @Response(401, body={"code": 40104, "message": "未登录的用户"})
     * })
     */
    public function refreshUser()
    {
        $user = $this->getAuthUser();
        if (!$user)
        {
            return $this->resErrAuth();
        }

        $user = $user->toArray();
        $userId = $user['id'];

        $imageRepository = new ImageRepository();
        $userRepository = new UserRepository();
        $userActivityService = new UserActivity();
        $userLevel = new UserLevel();

        $user['uptoken'] = $imageRepository->uptoken($userId);
        $user['daySign'] = $userRepository->daySigned($userId);
        $user['notification'] = $userRepository->getNotificationCount($userId);
        $user['exp'] = $userLevel->computeExpObject($user['exp']);
        $user['power'] = $userActivityService->get($userId);
        $user['providers'] = [
            'bind_qq' => !!$user['qq_unique_id'],
            'bind_wechat' => !!$user['wechat_unique_id'],
            'bind_phone' => !!$user['phone']
        ];
        if ($user['is_admin'])
        {
            $role = new Role();
            $user['roles'] = $role->roles($userId);
        }
        else
        {
            $user['roles'] = [];
        }

        $transformer = new UserTransformer();

        return $this->resOK($transformer->refresh($user));
    }

    /**
     * 刷新用户的 jwt-token
     *
     * 每次`启动应用`时调用，新的 token 会在 response header 里返回
     *
     * @Post("/door/refresh_token")
     *
     * @Request(headers={"Authorization": "Bearer JWT-Token"})
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": "success"}),
     *      @Response(401, body={"code": 40107, "message": "登录凭证获取失败"})
     * })
     */
    public function refreshJwtToken()
    {
        $userId = $this->getAuthUserId();
        if (!$userId)
        {
            return response([
                'code' => 40107,
                'message' => config('error.40107')
            ], 401);
        }

        return $this->resOK('success');
    }

    /**
     * APP 获取当前登录用户的信息
     *
     * 每次`启动应用`成功后调用
     *
     * @Post("/door/current_user")
     *
     * @Request(headers={"Authorization": "Bearer JWT-Token"})
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": "用户对象"}),
     *      @Response(401, body={"code": 40104, "message": "未登录的用户"})
     * })
     */
    public function currentUser()
    {
        $user = $this->getAuthUser();
        if (!$user)
        {
            return $this->resErrAuth();
        }

        $user = $user->toArray();
        $userId = $user['id'];

        $imageRepository = new ImageRepository();
        $userRepository = new UserRepository();
        $userActivityService = new UserActivity();
        $userLevel = new UserLevel();

        $user['uptoken'] = $imageRepository->uptoken($userId);
        $user['daySign'] = $userRepository->daySigned($userId);
        $user['notification'] = $userRepository->getNotificationCount($userId);
        $user['exp'] = $userLevel->computeExpObject($user['exp']);
        $user['power'] = $userActivityService->get($userId);
        $user['providers'] = [
            'bind_qq' => !!$user['qq_unique_id'],
            'bind_wechat' => !!$user['wechat_unique_id'],
            'bind_phone' => !!$user['phone']
        ];
        if ($user['is_admin'])
        {
            $role = new Role();
            $user['roles'] = $role->roles($userId);
        }
        else
        {
            $user['roles'] = [];
        }

        $transformer = new UserTransformer();

        return $this->resOK($transformer->refresh($user));
    }

    /**
     * 重置密码
     *
     * @Post("/door/reset")
     *
     * @Parameters({
     *      @Parameter("access", description="手机号", type="number", required=true),
     *      @Parameter("secret", description="6至16位的密码", type="string", required=true),
     *      @Parameter("authCode", description="6位数字的短信验证码", type="number", required=true)
     * })
     *
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": "密码重置成功"}),
     *      @Response(400, body={"code": 40001, "message": "未经过图形验证码认证"}),
     *      @Response(401, body={"code": 40100, "message": "图形验证码认证失败"}),
     *      @Response(400, body={"code": 40003, "message": "各种错误"})
     * })
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'access' => 'required|digits:11',
            'secret' => 'required|min:6|max:16',
            'authCode' => 'required|digits:6'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $access = $request->get('access');

        if (!$this->checkMessageAuthCode($access, 'forgot_password', $request->get('authCode')))
        {
            return $this->resErrBad('短信验证码过期，请重新获取');
        }

        $time = time();
        $remember_token = md5($time);

        User::where('phone', $access)
            ->update([
                'password' => bcrypt($request->get('secret')),
                'password_change_at' => $time,
                'remember_token' => $remember_token
            ]);

        return $this->resOK('密码重置成功');
    }

    // 创建营销号
    public function createFaker(Request $request)
    {
        $nickname = $request->get('nickname');
        $phone = $request->get('phone');
        $password = '$2y$10$zMAtJKR6iQyKyCJVItFBI.lJiVw/EN.nkvMawnFjMz2TOaW5gDSry';
        $zone = $this->createUserZone($nickname);

        $user = User::create([
            'nickname' => $nickname,
            'phone' => $phone,
            'password' => $password,
            'zone' => $zone,
            'faker' => 1
        ]);

        $userRepository = new UserRepository();
        $userRepository->migrateSearchIndex('C', $user->id);

        return $this->resCreated($user);
    }

    private function accessIsNew($method, $access)
    {
        return User::withTrashed()->where($method, $access)->count() === 0;
    }

    private function createUserZone($name)
    {
        $pinyin = strtolower(Overtrue::permalink($name));

        $tail = UserZone::where('name', $pinyin)->pluck('count')->first();

        // 如果用户的昵称是中文加数字，生成的拼音会有可能被占用，从而注册的时候就失败了
        // 可以通过一个递归调用 createUserZone 来解决，但是太危险了，先不修复这个问题

        if ($tail)
        {
            UserZone::where('name', $pinyin)->increment('count');
            return $pinyin . '-' . implode('-', str_split(($tail), 2));
        }
        else
        {
            UserZone::create(['name' => $pinyin]);

            return $pinyin;
        }
    }

    private function responseUser($user)
    {
        return JWTAuth::fromUser($user, [
            'remember' => $user->remember_token
        ]);
    }

    private function createMessageAuthCode($phone, $type)
    {
        $key = 'phone_message_' . $type . '_' . $phone;
        $value = rand(100000, 999999);

        Redis::SET($key, $value);
        Redis::EXPIRE($key, 300);

        return $value;
    }

    private function checkMessageAuthCode($phone, $type, $token)
    {
        $key = 'phone_message_' . $type . '_' . $phone;
        $value = Redis::GET($key);
        if (is_null($value))
        {
            return false;
        }

        Redis::DEL($key);
        return intval($value) === intval($token);
    }

    protected function getNickname($nickname)
    {
        preg_match_all('/([a-zA-Z]+|[0-9]+|[\x{4e00}-\x{9fa5}]+)*/u', $nickname, $matches);

        return implode('', $matches[0]) ?: 'zero';
    }
}
