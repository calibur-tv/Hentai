<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Repository;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    /**
     * 收藏图片（toggle）
     */
    public function mark(Request $request)
    {

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
                    "type": "$(mimeType)",
                    "size": $(fsize),
                    "url": "$(key)"
                }
            }',
            'mimeLimit' => 'image/jpeg;image/png;image/jpg;image/gif'
        ]);

        return $this->resOK($uptoken);
    }
}
