<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected function resOK($data = '')
    {
        return response([
            'code' => 0,
            'data' => $data
        ], 200);
    }

    protected function resErrBad($message = null)
    {
        return response([
            'code' => 400,
            'message' => $message ?: '请求参数错误'
        ], 400);
    }

    protected function resNoContent()
    {
        return response('', 204);
    }

    protected function resErrNotFound($message = null)
    {
        return response([
            'code' => 404,
            'message' => $message ?: '不存在的资源'
        ], 404);
    }

    protected function resErrLocked($message = null)
    {
        return response([
            'code' => 423,
            'message' => $message ?: '内容正在审核中'
        ], 423);
    }

    protected function resErrParams($validator)
    {
        return response([
            'code' => 400,
            'message' => $validator->errors()->all()[0]
        ], 400);
    }

    protected function resErrRole($message = null)
    {
        return response([
            'code' => 403,
            'message' => $message ?: '没有权限访问该页面'
        ], 403);
    }

    protected function slug2id($slug)
    {
        return floor(base_convert($slug, 36, 10) / 1000);
    }

    protected function id2slug($id)
    {
        return base_convert(($id * 1000 + rand(0, 999)), 10, 36);
    }

    protected function convertImagePath($url)
    {
        $arr = explode('calibur.tv/', $url);
        return count($arr) === 1 ? $url : explode('calibur.tv/', $url)[1];
    }
}
