<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function send_message(Request $request)
    {

    }

    /**
     * 用户注册
     */
    public function sign_up(Request $request)
    {
        // api_token 要 strtolower
        // 为用户创建一个默认收藏夹
    }

    /**
     * 用户登录
     */
    public function sign_in(Request $request)
    {

    }

    /**
     * 用户退出
     */
    public function sign_out(Request $request)
    {

    }

    /**
     * 重置密码
     */
    public function reset_password(Request $request)
    {

    }

    /**
     * 更新用户信息
     */
    public function update_info(Request $request)
    {

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
