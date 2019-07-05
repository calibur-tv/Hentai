<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Repositories\UserRepository;
use App\Http\Transformers\User\UserAuthResource;
use App\Services\Qiniu\Qshell;
use App\Services\Sms\Message;
use App\Services\WXBizDataCrypt;
use App\Services\Socialite\AccessToken;
use App\Services\Socialite\SocialiteManager;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\Qiniu\Http\Client;

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

        if ($this->checkMessageThrottle($phone))
        {
            return $this->resErrThrottle('一分钟内只能发送一次');
        }

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
     *      @Parameter("authCode", description="6位数字的短信验证码", type="number", required=true),
     *      @Parameter("inviteCode", description="邀请码", type="string", required=false),
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
            'authCode' => 'required|digits:6'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
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

        $data = [
            'password' => $request->get('secret'),
            'phone' => $access
        ];

        $inviteCode = $request->get('inviteCode');
        if ($inviteCode)
        {
            $userRepository = new UserRepository();
            $invitor = $userRepository->item($inviteCode);
            if ($invitor)
            {
                $data['invitor_slug'] = $inviteCode;
            }
        }

        $user = User::createUser($data);

        return $this->resCreated($user->api_token);
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

        return $this->resOK(new UserAuthResource($user));
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

        $socialite = new SocialiteManager(config('app.oauth2', []), $request);
        $accessToken = new AccessToken([
            'access_token' => $code
        ]);

        $user = $socialite
            ->driver('qq')
            ->user($accessToken);

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

            return $this->resOK();
        }

        if ($isNewUser)
        {
            $qshell = new Qshell();
            $avatar = $qshell->fetch($user['avatar']);
            // signUp
            $data = [
                'avatar' => $avatar,
                'nickname' => $user['nickname'],
                'qq_open_id' => $openId,
                'qq_unique_id' => $uniqueId,
                'sex' => $user['gender'] === '男' ? 1 : ($user['gender'] === '女' ? 2 : 0),
                'password' => str_rand()
            ];

            $user = User::createUser($data);
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

        return $this->resCreated($user->api_token);
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

        $socialite = new SocialiteManager(config('app.oauth2', []), $request);
        $accessToken = new AccessToken([
            'access_token' => $code,
            'openid' => $open_id
        ]);

        $user = $socialite
            ->driver('weixin')
            ->user($accessToken);

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

            return $this->resOK();
        }

        if ($isNewUser)
        {
            $qshell = new Qshell();
            $avatar = $qshell->fetch($user['avatar']);
            // signUp
            $data = [
                'avatar' => $avatar,
                'nickname' => $user['nickname'],
                'sex' => $user['sex'],
                'wechat_open_id' => $openId,
                'wechat_unique_id' => $uniqueId,
                'password' => str_rand()
            ];

            $user = User::createUser($data);
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

        return $this->resCreated($user->api_token);
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
                'password' => $request->get('password')
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
        $appId = config('app.oauth2.wechat_mini_app.app_id');

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
            $qshell = new Qshell();
            $avatar = $qshell->fetch($user['avatar']);
            // signUp
            $data = [
                'avatar' => $avatar,
                'nickname' => $user['nickname'],
                'sex' => $user['sex'],
                'wechat_open_id' => $data['openId'],
                'wechat_unique_id' => $uniqueId,
                'password' => str_rand()
            ];

            $user = User::createUser($data);
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

        return $this->resOK($user->api_token);
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
        $appId = config('app.oauth2.wechat_mini_app.app_id');
        $appSecret = config('app.oauth2.wechat_mini_app.app_secret');
        $resp = $client->get(
            "https://api.weixin.qq.com/sns/jscode2session?appid={$appId}&secret={$appSecret}&js_code={$code}&grant_type=authorization_code",
            [
                'Accept' => 'application/json'
            ]
        );
        $body = json_decode($resp->body, true);
        $uniqueId = isset($body['unionid']) ?? '';
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
            'data' => $user->api_token
        ]);
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

        $user = User
            ::where('phone', $access)
            ->first();
        if (is_null($user))
        {
            return $this->resErrNotFound();
        }

        $user->update([
                'password' => $request->get('secret')
            ]);

        $user->createApiToken();

        return $this->resOK('密码重置成功');
    }

    private function accessIsNew($method, $access)
    {
        return User::withTrashed()->where($method, $access)->count() === 0;
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

    private function checkMessageThrottle($phone)
    {
        $cacheKey = 'phone_message_throttle:' . $phone;
        if (Redis::EXISTS($cacheKey))
        {
            return true;
        }

        Redis::SET($cacheKey, 1);
        Redis::EXPIRE($cacheKey, 60);

        return false;
    }
}
