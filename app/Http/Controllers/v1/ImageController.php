<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\Geetest\Captcha;
use App\Services\Qiniu\Qshell;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ImageController extends Controller
{
    /**
     * 收藏图片（toggle）
     */
    public function mark(Request $request)
    {

    }

    /**
     * 获取 Geetest 验证码
     *
     * @Get("/image/captcha")
     *
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": {"success": "数字0或1", "gt": "Geetest.gt", "challenge": "Geetest.challenge", "payload": "字符串荷载"}})
     * })
     */
    public function captcha()
    {
        $captcha = new Captcha();

        return $this->resOK($captcha->get());
    }

    public function uptoken()
    {
        $auth = new \App\Services\Qiniu\Auth();
        $timeout = 3600;
        $uptoken = $auth->uploadToken(null, $timeout, [
            'returnBody' => '{
                "code": 0,
                "data": {
                    "height": $(imageInfo.height),
                    "width": $(imageInfo.width),
                    "mime": "$(mimeType)",
                    "size": $(fsize),
                    "url": "$(key)"
                }
            }',
            'mimeLimit' => 'image/jpeg;image/png;image/jpg;image/gif'
        ]);

        return $this->resOK($uptoken);
    }
}
