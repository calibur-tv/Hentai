<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-05-10
 * Time: 16:08
 */

namespace App\Http\Controllers;

use App\Services\Qiniu\Qshell;
use App\Services\Socialite\SocialiteManager;
use App\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * QQ第三方登录调用授权
     *
     * @Get("/callback/oauth2/qq")
     *
     * @Response(302)
     */
    public function qqAuthEntry(Request $request)
    {
        $socialite = new SocialiteManager(config('app.oauth2', []), $request);

        return $socialite
            ->driver('qq')
            ->redirect('https://api.calibur.tv/callback/auth/qq?' . http_build_query($request->all()));
    }

    // 微信开放平台登录 - PC
    public function wechatAuthEntry(Request $request)
    {
        $socialite = new SocialiteManager(config('app.oauth2', []), $request);

        return $socialite
            ->driver('wechat')
            ->redirect('https://api.calibur.tv/callback/auth/wechat?' . http_build_query($request->all()));
    }

    /**
     * 微信公众平台登录 - H5
     *
     * @Get("/callback/oauth2/weixin")
     *
     * @Response(302)
     */
    public function weixinAuthEntry(Request $request)
    {
        $socialite = new SocialiteManager(config('app.oauth2', []), $request);

        return $socialite
            ->driver('weixin')
            ->redirect('https://api.calibur.tv/callback/auth/weixin?' . http_build_query($request->all()));
    }

    public function qqAuthRedirect(Request $request)
    {
        $from = $request->get('from') === 'bind' ? 'bind' : 'sign';
        $code = $request->get('code');
        if (!$code)
        {
            return redirect('https://www.calibur.tv/callback/auth-error?message=' . '请求参数错误');
        }

        $socialite = new SocialiteManager(config('app.oauth2', []), $request);

        $user = $socialite
            ->driver('qq')
            ->user();

        $openId = $user['id'];
        $uniqueId = $user['unionid'];
        $isNewUser = $this->accessIsNew('qq_unique_id', $uniqueId);

        if ($from === 'bind')
        {
            if (!$isNewUser)
            {
                return redirect('https://www.calibur.tv/callback/auth-error?message=' . '该QQ号已绑定其它账号');
            }

            $token = $request->get('token');
            $hasUser = User
                ::where('api_token', $token)
                ->count();

            if (!$hasUser)
            {
                return redirect('https://www.calibur.tv/callback/auth-error?message=' . '继续操作前请先登录');
            }

            User
                ::where('api_token', $token)
                ->update([
                    'qq_open_id' => $openId,
                    'qq_unique_id' => $uniqueId
                ]);

            return redirect('https://www.calibur.tv/callback/auth-success?message=' . '已成功绑定QQ号');
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
                return redirect('https://www.calibur.tv/callback/auth-error?message=' . '这个用户消失了');
            }
        }

        return redirect('https://www.calibur.tv/callback/auth-redirect?message=登录成功&token=' . $user->api_token . '&redirect=' . $request->get('redirect'));
    }

    public function wechatAuthRedirect(Request $request)
    {
        $from = $request->get('from') === 'bind' ? 'bind' : 'sign';
        $code = $request->get('code');
        if (!$code)
        {
            return redirect('https://www.calibur.tv/callback/auth-error?message=' . '请求参数错误');
        }

        $socialite = new SocialiteManager(config('app.oauth2', []), $request);

        $user = $socialite
            ->driver('wechat')
            ->user();

        $openId = $user['original']['openid'];
        $uniqueId = $user['original']['unionid'];
        $isNewUser = $this->accessIsNew('wechat_unique_id', $uniqueId);

        if ($from === 'bind')
        {
            if (!$isNewUser)
            {
                return redirect('https://www.calibur.tv/callback/auth-error?message=' . '该微信号已绑定其它账号');
            }

            $token = $request->get('token');
            $hasUser = User
                ::where('api_token', $token)
                ->count();

            if (!$hasUser)
            {
                return redirect('https://www.calibur.tv/callback/auth-error?message=' . '继续操作前请先登录');
            }

            User
                ::where('api_token', $token)
                ->update([
                    'wechat_open_id' => $openId,
                    'wechat_unique_id' => $uniqueId
                ]);

            return redirect('https://www.calibur.tv/callback/auth-success?message=' . '已成功绑定微信号');
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
        }

        return redirect('https://www.calibur.tv/callback/auth-redirect?message=登录成功&token=' . $user->api_token . '&redirect=' . $request->get('redirect'));
    }

    public function weixinAuthRedirect(Request $request)
    {
        $from = $request->get('from') === 'bind' ? 'bind' : 'sign';
        $code = $request->get('code');
        if (!$code)
        {
            return redirect('https://www.calibur.tv/callback/auth-error?message=' . '请求参数错误');
        }

        $socialite = new SocialiteManager(config('app.oauth2', []), $request);

        $user = $socialite
            ->driver('weixin')
            ->user();

        $openId = $user['original']['openid'];
        $uniqueId = $user['original']['unionid'];
        $isNewUser = $this->accessIsNew('wechat_unique_id', $uniqueId);

        if ($from === 'bind')
        {
            if (!$isNewUser)
            {
                return redirect('https://www.calibur.tv/callback/auth-error?message=' . '该微信号已绑定其它账号');
            }

            $token = $request->get('token');
            $hasUser = User
                ::where('api_token', $token)
                ->count();

            if (!$hasUser)
            {
                return redirect('https://www.calibur.tv/callback/auth-error?message=' . '继续操作前请先登录');
            }

            User
                ::where('api_token', $token)
                ->update([
                    'wechat_open_id' => $openId,
                    'wechat_unique_id' => $uniqueId
                ]);

            return redirect('https://www.calibur.tv/callback/auth-success?message=' . '已成功绑定微信号');
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
        }

        return redirect('https://www.calibur.tv/callback/auth-redirect?message=登录成功&token=' . $user->api_token . '&redirect=' . $request->get('redirect'));
    }

    private function accessIsNew($method, $access)
    {
        return User::withTrashed()->where($method, $access)->count() === 0;
    }
}
