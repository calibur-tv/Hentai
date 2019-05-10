<?php

namespace App\Http\Middleware;

use App\Services\Geetest\Captcha;
use Closure;

class GeetestMiddleware
{
    public function handle($request, Closure $next)
    {
        $geetest = $request->input('geetest');

        if (is_null($geetest))
        {
            return response([
                'code' => 400,
                'message' => '未经验证的请求'
            ], 400);
        }

        $captcha = new Captcha();

        if (!$captcha->validate($geetest))
        {
            return response([
                'code' => 401,
                'message' => '验证码认证失败'
            ], 401);
        }

        return $next($request);
    }
}
